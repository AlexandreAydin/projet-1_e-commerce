// Fonction pour formater le prix
export const formatPrice = (price) => {
    return Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' })
        .format(price);
}

// Fonction générique pour récupérer les données d'une requête
async function fetchData(requestUrl) {
    try {
        const response = await fetch(requestUrl);

        // Vérifiez si la réponse est bien JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Réponse non JSON reçue');
        }

        if (!response.ok) {
            throw new Error(`Erreur HTTP : ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Erreur lors de la récupération des données:', error.message);
        return null;
    }
}


export const addWishListEventListenerToLink = () => {
    let links = document.querySelectorAll(".add-to-wishlist, .wishlist_table .remove-to-wishlist");
    links.forEach(link => {
        link.addEventListener("click", manageWishListLink);
    });
}

// Fonction pour gérer la wishlist
export const manageWishListLink = async (event) => {
    event.preventDefault();

    const link = event.target.closest('a');
    if (!link) return;

    const requestUrl = link.href;

    console.log('Request URL:', requestUrl);

    try {
        const response = await fetch(requestUrl, { method: 'POST' });

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Réponse non JSON reçue');
        }

        if (!response.ok) {
            throw new Error(`Erreur HTTP : ${response.status}`);
        }

        const result = await response.json();
        console.log('Wishlist mise à jour:', result);

        if (result.success) {
            link.classList.toggle('wishlist-active'); // Changement d'état visuel
            console.log(result.message);
        } else {
            console.error('Erreur:', result.message);
        }
    } catch (err) {
        console.error('Erreur lors de la gestion de la liste de souhaits:', err.message);
    }
};


document.addEventListener('DOMContentLoaded', function () {
    // Ajoute un gestionnaire d'événements à tous les boutons de suppression
    document.querySelectorAll('.remove-wishlist-item').forEach(button => {
        button.addEventListener('click', async function (event) {
            event.preventDefault(); // Empêche le comportement par défaut du lien

            const productId = button.getAttribute('data-product-id'); // ID du produit
            const url = `/mes-favoris/${productId}/supprimer`; // URL pour la suppression
            const row = button.closest('tr'); // La ligne du tableau à supprimer

            try {
                // Effectue une requête DELETE
                const response = await fetch(url, { method: 'DELETE' });
                const result = await response.json();

                if (response.ok && result.success) {
                    // Supprime dynamiquement la ligne du tableau
                    row.remove();
                    console.log(`Produit ${productId} supprimé de la wishlist.`);
                } else {
                    alert(result.message || 'Une erreur est survenue.');
                }
            } catch (error) {
                console.error('Erreur lors de la suppression de la wishlist:', error.message);
            }
        });
    });
});


export const displayWishlist = (wishlist = null) => {
    addWishListEventListenerToLink();

    if (!wishlist) return;

    let tbody = document.querySelector('.wishlist_table tbody');
    if (tbody) {
        tbody.innerHTML = "";
        wishlist.forEach((product) => {
            const imageUrl = product.images ? `/images/products/${product.images}` : '/images/placeholder-image.jpg';
            let content = `
                <tr>
                    <td class="product-thumbnail"></td>
                    <td class="product-name"><a href="/produit/${product.slug}">${product.name}</a></td>
                    <td class="product-price"></td>
                    <td class="add-to-cart"></td>
                    <td class="product-thumbnail"></td>
                    <td class="remove-to-wishlist">
                    </td>
                </tr>
            `;
            tbody.innerHTML += content;
        });
    }
}

export const initCart = (cart = null) => {
    const tbody = document.querySelector('.cart_table tbody');
    const cartSubtotalElement = document.querySelector('.cart_subtotal');
    const cartTaxeElement = document.querySelector('.cart_taxe');
    const cartTotalElement = document.querySelector('.cart_total');

    if (!cart || !cart.products) {
        console.warn('Aucun produit dans le panier.');
        return;
    }

    // Réinitialiser le tableau avant de le remplir
    if (tbody) {
        tbody.innerHTML = '';
        cart.products.forEach(item => {
            const { product, variant, quantity } = item;
            const imageUrl = product.images && product.images.length > 0
                ? `/images/products/${variant.images[0]}`
                : '/images/products/default.jpg';

            const deletePath = `/mon-panier/${product.id}/supprimer`;
            const addPath = `/mon-panier/${product.id}/ajouter`;
            const deleteAllPath = `/mon-panier/${product.id}/tout-supprimer`;

            const content = `
                <tr>
                    <td class="product-thumbnail">
                        <a href="#"><img src="${imageUrl}" alt="${product.name}"></a>
                    </td>
                    <td class="product-name">${product.name}</td>
                    <td class="product-price">${(variant.price / 100).toFixed(2)}</td>
                    <td class="product-quantity">
                        <div class="quantity">
                            <a href="${deletePath}" class="minus">-</a>
                            <input type="text" value="${quantity}" class="qty" readonly>
                            <a href="${addPath}" class="plus">+</a>
                        </div>
                    </td>
                    <td class="product-remove">
                        <a href="${deleteAllPath}" class="item_remove">×</a>
                    </td>
                </tr>
            `;

            tbody.insertAdjacentHTML('beforeend', content);
        });
    }

    // Mettre à jour les totaux
    if (cartSubtotalElement) cartSubtotalElement.textContent = cart.data.subTotalHT.toFixed(2);
    if (cartTaxeElement) cartTaxeElement.textContent = cart.data.Taxe.toFixed(2);
    if (cartTotalElement) cartTotalElement.textContent = cart.data.subTotalTTC.toFixed(2);

    // Ajout des événements aux liens dynamiques
    addEventListenerToLink(); // Assurez-vous que cette fonction est bien définie
};


const addEventListenerToLink = () => {
    const links = document.querySelectorAll('.cart_list a.plus, .cart_list a.minus, .cart_list a.item_remove');
    links.forEach(link => {
        link.addEventListener('click', manageCartLink);
    });
};

function updateCartQuantityInDOM(uniqueKey, quantity) {
    const qtyElement = document.querySelector(`li[data-variant-key="${uniqueKey}"] .qty`);
    if (qtyElement) {
        qtyElement.value = quantity;
        console.log(`Quantité mise à jour pour la clé ${uniqueKey} : ${quantity}`);
    }
}

export const updateHeaderCart = (cart) => {
    console.log('Données reçues pour mettre à jour le header :', cart);

    const cartListElement = document.querySelector('.cart_list');
    const cartCountElement = document.querySelector('.cart_count');
    const cartPriceValue = document.querySelector('.cart_price_value');
    const cartPriceTaxe = document.querySelector('.cart_price_taxe');
    const cartPriceTTC = document.querySelector('.cart_price_ttc');

    // Vérification si le panier est vide
    if (!cart || !cart.products || cart.products.length === 0) {
        console.log('Le panier est vide, mise à jour en conséquence.');

        if (cartListElement) {
            cartListElement.innerHTML = '<li class="empty-cart">Votre panier est vide.</li>';
        }

        if (cartPriceValue) cartPriceValue.innerHTML = '0.00 €';
        if (cartPriceTaxe) cartPriceTaxe.innerHTML = '0.00 €';
        if (cartPriceTTC) cartPriceTTC.innerHTML = '0.00 €';
        if (cartCountElement) cartCountElement.textContent = '0';

        return;
    }

    // Mise à jour des totaux globaux
    if (cartPriceValue) {
        cartPriceValue.innerHTML = `${cart.data.subTotalHT.toFixed(2)} €`;
    }
    if (cartPriceTaxe) {
        cartPriceTaxe.innerHTML = `${cart.data.Taxe.toFixed(2)} €`;
    }
    if (cartPriceTTC) {
        cartPriceTTC.innerHTML = `${cart.data.subTotalTTC.toFixed(2)} €`;
    }
    if (cartCountElement) {
        cartCountElement.textContent = cart.data.cart_count;
    }

    // Réinitialisation et mise à jour des produits dans le DOM
    if (cartListElement) {
        cartListElement.innerHTML = ''; // Vide la liste avant de la remplir

        cart.products.forEach((item) => {
            const { variant, quantity, product } = item;

            // Construire une clé unique pour éviter les doublons
            const uniqueKey = `${variant.id}-${variant.size}-${variant.color}`;

            // Calcul du prix TTC avec remise
            const discount = variant.offVariant ? (variant.offVariant / 100) : 0;
            const discountedPriceTTC = variant.price * (1 - discount);
            // Vérifier si l'image existe
            const imageUrl =
                product.images && product.images.length > 0
                    ? `/images/products/${product.images[0]}`
                    : '/images/products/default.jpg';

            // HTML pour chaque produit
            const content = `
                <li data-variant-key="${uniqueKey}">
                    <a href="/produit/${product.slug}" class="product-thumbnail">
                        <img src="${imageUrl}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;">
                        ${product.name} (${variant.size || 'Default'}, ${variant.color || 'Default'})
                    </a>
                    <a href="/mon-panier/${variant.id}/tout-supprimer" 
                        class="item_remove" 
                        data-variant-id="${variant.id}" 
                        data-size="${variant.size}" 
                        data-color="${variant.color}">
                            <i class="ion-close"></i>
                        </a>

                    <div class="cart-product-quantity mb-4">
                        <div class="quantity">
                            <a href="/mon-panier/${variant.id}/diminuer" class="minus" data-variant-id="${variant.id}" data-size="${variant.size}" data-color="${variant.color}">-</a>
                            <input type="text" value="${quantity}" class="qty" readonly>
                            <a href="/mon-panier/${variant.id}/ajouter" class="plus" data-variant-id="${variant.id}" data-size="${variant.size}" data-color="${variant.color}">+</a>
                        </div>
                    </div>
                    <span class="cart_quantity text-dark qty">${quantity} x <span class="cart_amount">${discountedPriceTTC.toFixed(2)} €</span></span>
                </li>
            `;
            cartListElement.insertAdjacentHTML('beforeend', content);
        });
    }

    console.log('Réattachement des événements.');

    // Réattachement des événements nécessaires
    attachQuantityChangeEvents(); // Assurez-vous que cette fonction existe pour gérer les boutons "plus" et "moins"
};

document.querySelectorAll('.plus').forEach(button => {
    button.addEventListener('click', async function (event) {
        event.preventDefault();

        const link = event.target.closest('a');
        if (!link) return;

        const variantId = link.getAttribute('data-variant-id');
        const size = link.getAttribute('data-size') || 'DefaultSize';
        const color = link.getAttribute('data-color') || 'DefaultColor';

        const url = `/panier/${variantId}/ajouter/1?size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}`;

        try {
            const response = await fetch(url, { method: 'POST' });
            if (!response.ok) {
                throw new Error('Erreur lors de l\'ajout de quantité');
            }

            const updatedCart = await response.json();
            console.log('Réponse serveur (panier mis à jour) :', updatedCart);

            updateHeaderCart(updatedCart);
        } catch (error) {
            console.error('Erreur :', error.message);
        }
    });
});

document.querySelectorAll('.minus').forEach(button => {
    button.addEventListener('click', async function (event) {
        event.preventDefault();

        const variantId = button.closest('li').dataset.variantId; // Identifiant de la variante
        const size = button.closest('li').dataset.size; // Taille associée
        const color = button.closest('li').dataset.color; // Couleur associée

        console.log(`Réduction pour le variant ID: ${variantId}, Taille: ${size}, Couleur: ${color}`);

        const url = `/panier/${variantId}/diminuer?size=${size}&color=${color}`;

        try {
            const response = await fetch(url, { method: 'POST' });
            if (!response.ok) {
                throw new Error('Erreur lors de la réduction de quantité');
            }

            const updatedCart = await response.json();
            console.log('Panier mis à jour (réponse serveur):', updatedCart);

            // Mettre à jour dynamiquement l'interface utilisateur
            updateHeaderCart(updatedCart);
        } catch (error) {
            console.error('Erreur :', error.message);
        }
    });
});

document.querySelectorAll('.item_remove').forEach(button => {
    button.addEventListener('click', async function (event) {
        event.preventDefault();

        const variantId = button.closest('li').dataset.variantId; // Identifiant de la variante
        const size = button.closest('li').dataset.size; // Taille associée
        const color = button.closest('li').dataset.color; // Couleur associée

        console.log(`Réduction pour le variant ID: ${variantId}, Taille: ${size}, Couleur: ${color}`);

        const url = `/panier/${variantId}/tout-supprimer?size=${size}&color=${color}`;

        try {
            const response = await fetch(url, { method: 'POST' });
            if (!response.ok) {
                throw new Error('Erreur lors de la réduction de quantité');
            }

            const updatedCart = await response.json();
            console.log('Panier mis à jour (réponse serveur):', updatedCart);

            // Mettre à jour dynamiquement l'interface utilisateur
            updateHeaderCart(updatedCart);
        } catch (error) {
            console.error('Erreur :', error.message);
        }
    });
});

document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Appeler la route pour obtenir les données du panier
        const cart = await fetchData('/mon-panier/obtenir');
        console.log('Panier récupéré au chargement:', cart);

        if (cart && cart.products) {
            updateHeaderCart(cart); // Mettre à jour dynamiquement le panier dans le header
        }
    } catch (error) {
        console.error('Erreur lors du chargement du panier :', error.message);
    }
});

// Fonction principale pour gérer les liens dynamiques
const manageCartLink = async (event) => {
    event.preventDefault();

    const link = event.target.closest('a');
    if (!link) return;

    const requestUrl = link.href;
    console.log('Lien cliqué :', requestUrl);

    try {
        const response = await fetch(requestUrl, { method: 'POST' });
        if (!response.ok) {
            throw new Error(`Erreur HTTP : ${response.status}`);
        }

        const updatedCart = await response.json();
        console.log('Réponse serveur (panier mis à jour) :', updatedCart);

        updateHeaderCart(updatedCart); // Met à jour le header dynamiquement
    } catch (err) {
        console.error('Erreur lors de la mise à jour du panier :', err.message);
    }
};


// Ajoute des événements aux liens pertinents
const addEventListenerToCartLinks = () => {
    const links = document.querySelectorAll('a.plus, a.minus, a.item_remove');
    links.forEach(link => {
        console.log(`Événement attaché pour : ${link.className}`);
        link.addEventListener('click', manageCartLink);
    });
};

// Recharge les événements au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    addEventListenerToCartLinks();
});


document.addEventListener('DOMContentLoaded', () => {
    // Attacher les événements aux boutons plus et moins
    attachQuantityChangeEvents();
});

const attachQuantityChangeEvents = () => {
    const plusButtons = document.querySelectorAll('.plus');
    const minusButtons = document.querySelectorAll('.minus');
    const removeButtons = document.querySelectorAll('.item_remove');

    plusButtons.forEach(button => {
        button.addEventListener('click', event => handleQuantityChange(event, 'increase'));
    });

    minusButtons.forEach(button => {
        button.addEventListener('click', event => handleQuantityChange(event, 'decrease'));
    });

    removeButtons.forEach(button => {
        button.addEventListener('click', event => handleQuantityChange(event, 'remove'));
    });
};


async function handleQuantityChange(event, action) {
    event.preventDefault();
    const button = event.target.closest('a');
    if (!button) return;

    const variantId = button.getAttribute('data-variant-id');
    const uniqueKey = button.closest('li[data-variant-key]').getAttribute('data-variant-key');

    // Récupérer la taille et la couleur associées
    const size = button.getAttribute('data-size') || 'DefaultSize';
    const color = button.getAttribute('data-color') || 'DefaultColor';

    let requestUrl = `/mon-panier/${variantId}`;
    if (action === 'increase') {
        requestUrl += `/ajouter?size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}`;
    } else if (action === 'decrease') {
        requestUrl += `/diminuer?size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}`;
    }else if (action === 'remove') {
        requestUrl += `/tout-supprimer?size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}`;
    }

    try {
        const response = await fetch(requestUrl, { method: 'POST' });
        if (!response.ok) {
            throw new Error(`Erreur HTTP : ${response.status}`);
        }

        const updatedCart = await response.json();
        console.log('Réponse serveur (panier mis à jour) :', updatedCart);

        // Mettre à jour la quantité dans le DOM
        const updatedProduct = updatedCart.products.find(
            item => `${item.variant.id}-${item.variant.size}-${item.variant.color}` === uniqueKey
        );

        if (updatedProduct) {
            updateCartQuantityInDOM(uniqueKey, updatedProduct.quantity);
        } else {
            // Si la quantité atteint zéro, supprimez l'élément
            removeCartItemFromDOM(uniqueKey);
        }

        // Mettre à jour le header du panier
        updateHeaderCart(updatedCart);
    } catch (error) {
        console.error('Erreur lors de la mise à jour du panier :', error.message);
    }
}

function removeCartItemFromDOM(uniqueKey) {
    const cartItem = document.querySelector(`li[data-variant-key="${uniqueKey}"]`);
    if (cartItem) {
        cartItem.remove();
        console.log(`Élément supprimé pour la clé ${uniqueKey}`);
    }
}

// document.querySelectorAll('.btn-addtocart').forEach(button => {
//     button.addEventListener('click', async (event) => {
//         event.preventDefault();

//         const variantId = button.dataset.variantId;
//         const size = document.querySelector('select[name="size"]').value;
//         const color = document.querySelector('select[name="color"]').value;
//         const quantity = parseInt(document.querySelector('input#quantity').value, 10) || 1;

//         console.log(`Variant ID: ${variantId}, Taille: ${size}, Couleur: ${color}, Quantité: ${quantity}`);

//         const url = `/panier/${variantId}/ajouter/${quantity}?size=${size}&color=${color}`;

//         try {
//             const response = await fetch(url, { method: 'POST' });
//             const data = await response.json();
//             console.log('Réponse du serveur :', data);

//             if (data.products) {
//                 updateHeaderCart(data);
//             }
//         } catch (error) {
//             console.error('Erreur :', error.message);
//         }
//     });
// });


// document.querySelector('.btn-addtocart').addEventListener('click', async (event) => {
//     button.addEventListener('click', async (event) => {

//     const size = document.querySelector('select[name="size"]').value;
//     const color = document.querySelector('select[name="color"]').value;
//     const variant = getVariantBySizeAndColor(size, color);

//     if (!variant) {
//         console.error('Aucune variante trouvée pour cette combinaison.');
//         return;
//     }

//     console.log(`Envoi au serveur : Variant ID = ${variant.id}, Taille = ${size}, Couleur = ${color}`);

//     const url = `/panier/${variant.id}/ajouter/1?size=${size}&color=${color}`;

//     try {
//         const response = await fetch(url, { method: 'POST' });
//         const data = await response.json();
//         console.log('Réponse du serveur :', data);
//     } catch (error) {
//         console.error('Erreur lors de l\'ajout au panier :', error.message);
//     }
// });
// });

document.querySelectorAll('.btn-addtocart').forEach(button => {
    button.addEventListener('click', async (event) => {
        event.preventDefault();

        // Récupérer les valeurs sélectionnées
        const size = document.querySelector('select[name="size"]').value;
        const color = document.querySelector('select[name="color"]').value;
        const quantity = parseInt(document.querySelector('input#quantity').value, 10) || 1;

        // Trouver la variante correspondante
        const variant = getVariantBySizeAndColor(size, color);

        if (!variant) {
            console.error('Aucune variante trouvée pour cette combinaison.');
            return;
        }

        // Utiliser l'ID de la variante correcte
        const variantId = variant.id;

        console.log(`Envoi au serveur : Variant ID = ${variantId}, Taille = ${size}, Couleur = ${color}, Quantité = ${quantity}`);

        // Construire l'URL de la requête
        const url = `/panier/${variantId}/ajouter/${quantity}?size=${size}&color=${color}`;

        try {
            const response = await fetch(url, { method: 'POST' });
            const data = await response.json();

            console.log('Réponse du serveur :', data);

            // Mettre à jour le panier dans le header
            if (data.products) {
                updateHeaderCart(data);
            }
        } catch (error) {
            console.error('Erreur lors de l\'ajout au panier :', error.message);
        }
    });
});

// Fonction pour trouver la variante par taille et couleur
function getVariantBySizeAndColor(size, color) {
    // Vérifier si les données de variantes sont valides
    if (!Array.isArray(variants) || variants.length === 0) {
        console.error("Aucune variante disponible.");
        return null;
    }

    // Trouver une variante correspondant à la taille et la couleur
    const matchingVariant = variants.find(variant => {
        const sizeArray = Array.isArray(variant.sizes) ? variant.sizes : [];
        return variant.color === color && sizeArray.includes(size);
    });

    if (!matchingVariant) {
        console.warn(`Aucune variante trouvée pour la taille "${size}" et la couleur "${color}"`);
    }

    return matchingVariant;
}



function updateVariantId() {
    const size = document.querySelector('select[name="size"]').value;
    const color = document.querySelector('select[name="color"]').value;

    // Trouver la variante correspondante
    const variant = variants.find(v => v.size === size && v.color === color);
    if (variant) {
        const button = document.querySelector('.btn-addtocart');
        button.dataset.variantId = variant.id; // Mettre à jour l'ID de la variante
        console.log('ID de la variante mis à jour :', variant.id);
    }
}




const transformCartResponse = (serverResponse) => {
    const products = Object.values(serverResponse).map(item => ({
        product: item.product,
        variant: item.variant,
        quantity: item.quantity,
    }));

    const cartData = {
        products,
        data: {
            cart_count: products.reduce((sum, item) => sum + item.quantity, 0),
            subTotalHT: products.reduce((sum, item) => sum + item.variant.price * item.quantity, 0),
            Taxe: products.reduce((sum, item) => sum + item.variant.price * item.quantity * 0.2, 0),
            subTotalTTC: products.reduce((sum, item) => sum + item.variant.price * item.quantity * 1.2, 0),
        }
    };

    return cartData;
};





