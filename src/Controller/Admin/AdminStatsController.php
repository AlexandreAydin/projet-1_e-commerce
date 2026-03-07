<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository
    ) {}

    #[Route('/admin/stats', name: 'admin_stats_page', methods: ['GET'])]
    public function statsPage(): Response
    {
        return $this->render('admin/stats.html.twig');
    }

    #[Route('/admin/api/stats', name: 'admin_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        try {
            $preset     = $request->query->get('preset', '30d');
            $customFrom = $request->query->get('from');
            $customTo   = $request->query->get('to');

            [$dateFrom, $dateTo] = $this->resolveDates($preset, $customFrom, $customTo);

            // ── Commandes payées sur la période ───────────────────────────────
            /** @var Order[] $orders */
            $orders = $this->em->createQueryBuilder()
                ->select('o')
                ->from(Order::class, 'o')
                ->where('o.createdAt BETWEEN :from AND :to')
                ->andWhere('o.isPaid = true')
                ->setParameter('from', $dateFrom)
                ->setParameter('to',   $dateTo)
                ->orderBy('o.createdAt', 'ASC')
                ->getQuery()
                ->getResult();

            // ── Grouper par jour / semaine / mois ─────────────────────────────
            $diffDays = (int) $dateFrom->diff($dateTo)->days;
            $groupBy  = $diffDays > 365 ? 'month' : ($diffDays > 60 ? 'week' : 'day');

            $seriesMap = [];
            foreach ($orders as $order) {
                $key = match ($groupBy) {
                    'month' => $order->getCreatedAt()->format('Y-m'),
                    'week'  => $order->getCreatedAt()->format('Y-W'),
                    default => $order->getCreatedAt()->format('Y-m-d'),
                };

                if (!isset($seriesMap[$key])) {
                    $seriesMap[$key] = [
                        'label'   => $this->formatLabel($order->getCreatedAt(), $groupBy),
                        'orders'  => 0,
                        'revenue' => 0.0,
                        'ht'      => 0.0,
                        'tva'     => 0.0,
                    ];
                }

                $seriesMap[$key]['orders']++;
                // toEuros() gère automatiquement centimes ou euros
                $seriesMap[$key]['revenue'] += $this->toEuros($order->getSubTotalTTC());
                $seriesMap[$key]['ht']      += $this->toEuros($order->getSubTotalHT());
                $seriesMap[$key]['tva']     += $this->toEuros($order->getTaxe());
            }

            $series = array_values($seriesMap);

            // ── KPI ───────────────────────────────────────────────────────────
            $totalOrders  = count($orders);
            $totalRevenue = (float) array_sum(array_column($series, 'revenue'));
            $totalHT      = (float) array_sum(array_column($series, 'ht'));
            $totalTVA     = (float) array_sum(array_column($series, 'tva'));
            $avgRevenue   = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0;
            $bestDay      = $series ? (float) max(array_column($series, 'revenue')) : 0.0;

            // ── Statuts (toutes commandes, période) ───────────────────────────
            $stateLabels = [0=>'Non payée',1=>'Payée',2=>'En préparation',3=>'En livraison',4=>'Livré'];

            $stateCounts = $this->em->createQueryBuilder()
                ->select('o.state, COUNT(o.id) as cnt')
                ->from(Order::class, 'o')
                ->where('o.createdAt BETWEEN :from AND :to')
                ->setParameter('from', $dateFrom)
                ->setParameter('to',   $dateTo)
                ->groupBy('o.state')
                ->getQuery()
                ->getResult();

            $states = array_map(fn($r) => [
                'state' => (int) $r['state'],
                'label' => $stateLabels[$r['state']] ?? 'Inconnu',
                'count' => (int) $r['cnt'],
            ], $stateCounts);

            // ── Méthodes de paiement ──────────────────────────────────────────
            $paymentCounts = $this->em->createQueryBuilder()
                ->select('o.paymentMethod, COUNT(o.id) as cnt')
                ->from(Order::class, 'o')
                ->where('o.createdAt BETWEEN :from AND :to')
                ->andWhere('o.isPaid = true')
                ->setParameter('from', $dateFrom)
                ->setParameter('to',   $dateTo)
                ->groupBy('o.paymentMethod')
                ->getQuery()
                ->getResult();

            // ── Période précédente ────────────────────────────────────────────
            // Utilise getOneOrNullResult() au lieu de getSingleResult()
            // pour éviter le crash si aucune commande n'existe sur cette période
            $interval     = $dateFrom->diff($dateTo);
            $prevDateTo   = clone $dateFrom;
            $prevDateFrom = (clone $dateFrom)->sub($interval);

            $prevRow = $this->em->createQueryBuilder()
                ->select('COUNT(o.id) as cnt, SUM(o.subTotalTTC) as revenue')
                ->from(Order::class, 'o')
                ->where('o.createdAt BETWEEN :from AND :to')
                ->andWhere('o.isPaid = true')
                ->setParameter('from', $prevDateFrom)
                ->setParameter('to',   $prevDateTo)
                ->getQuery()
                ->getOneOrNullResult();   // ← corrige le crash "No result"

            $prevOrders  = (int)   ($prevRow['cnt'] ?? 0);
            $prevRevenue = (float) $this->toEuros($prevRow['revenue'] ?? 0);

            $trendOrders  = $prevOrders  > 0
                ? round((($totalOrders  - $prevOrders)  / $prevOrders)  * 100, 1) : 0;
            $trendRevenue = $prevRevenue > 0
                ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

            return new JsonResponse([
                'kpi' => [
                    'totalOrders'   => $totalOrders,
                    'totalRevenue'  => round($totalRevenue, 2),
                    'totalHT'       => round($totalHT, 2),
                    'totalTVA'      => round($totalTVA, 2),
                    'avgOrderValue' => $avgRevenue,
                    'bestPeriod'    => round($bestDay, 2),
                    'trendOrders'   => $trendOrders,
                    'trendRevenue'  => $trendRevenue,
                ],
                'series'         => $series,
                'states'         => $states,
                'paymentMethods' => $paymentCounts,
                'period'         => [
                    'from'    => $dateFrom->format('Y-m-d'),
                    'to'      => $dateTo->format('Y-m-d'),
                    'days'    => $diffDays,
                    'groupBy' => $groupBy,
                ],
            ]);

        } catch (\Throwable $e) {
            // Retourne le vrai message d'erreur en JSON pour aider au debug
            // Retire ce catch en production
            return new JsonResponse([
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file'  => basename($e->getFile()) . ':' . $e->getLine(),
                'trace' => array_slice(array_map(
                    fn($t) => ($t['class'] ?? '') . '::' . ($t['function'] ?? '') . ' — ' . basename($t['file'] ?? '?') . ':' . ($t['line'] ?? '?'),
                    $e->getTrace()
                ), 0, 6),
            ], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * EasyAdmin MoneyField stocke en centimes entiers (ex: 1999 = 19,99€).
     * Si la valeur est > 10 000 et entière → on divise par 100.
     * Sinon on considère que c'est déjà en euros.
     */
    /**
     * EasyAdmin MoneyField stocke TOUJOURS en centimes (int).
     * Ex: 9373 → 93,73 € | 1999 → 19,99 €
     */
    private function toEuros(mixed $value): float
    {
        return round((float) ($value ?? 0) / 100, 2);
    }

    private function resolveDates(string $preset, ?string $from, ?string $to): array
    {
        $now   = new DateTime();
        $today = new DateTime('today');

        return match ($preset) {
            'today'      => [new DateTime('today 00:00:00'),     new DateTime('today 23:59:59')],
            'yesterday'  => [new DateTime('yesterday 00:00:00'), new DateTime('yesterday 23:59:59')],
            '7d'         => [(clone $today)->modify('-6 days'),  $now],
            '30d'        => [(clone $today)->modify('-29 days'), $now],
            'mtd'        => [new DateTime('first day of this month 00:00:00'), $now],
            'prev_month' => [
                new DateTime('first day of last month 00:00:00'),
                new DateTime('last day of last month 23:59:59'),
            ],
            '12m'        => [(clone $today)->modify('-364 days'), $now],
            'ytd'        => [new DateTime('first day of January this year 00:00:00'), $now],
            'lifetime'   => [new DateTime('2000-01-01'), $now],
            'custom'     => [
                $from ? new DateTime($from . ' 00:00:00') : (clone $today)->modify('-29 days'),
                $to   ? new DateTime($to   . ' 23:59:59') : $now,
            ],
            default => [(clone $today)->modify('-29 days'), $now],
        };
    }

    private function formatLabel(DateTimeInterface $date, string $groupBy): string
    {
        return match ($groupBy) {
            'month' => $date->format('M Y'),
            'week'  => 'Sem. ' . $date->format('W'),
            default => $date->format('d/m'),
        };
    }
}