// Fonction pour formater le prix
export const formatPrice = (price) => {
    return Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' })
        .format(price);
}

// Fonction générique pour récupérer les données d'une requête
async function fetchData(requestUrl) {
    try {
        const response = await fetch(requestUrl);
        if (!response.ok) {
            throw new Error('Erreur réseau');
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
    if (link.classList.contains('view-details')) {
        window.location.href = requestUrl;
        return;
    }

    if (requestUrl.includes('/mes-favoris/supprimer/')) {
        try {
            const data = await fetchDataWithMethod(requestUrl, 'DELETE');
            if (data && data.success) {
                const tableRow = link.closest('tr');
                tableRow.parentNode.removeChild(tableRow);
                addFlashMessage(`Produit supprimé de la liste de souhaits !`, "danger");
            }
        } catch (err) {
            console.error('Erreur lors de la suppression des favoris:', err);
        }
        return;
    }

    try {
        const wishlist = await fetchData(requestUrl);
        initCart();
        updateHeaderCart();
        displayWishlist(wishlist);
    } catch (err) {
        console.error('Erreur lors de la gestion de la liste de souhaits:', err);
    }
}

// Fonction pour afficher la wishlist
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
                    <td class="product-thumbnail"><a href="#"><img src="${imageUrl}" alt="${product.name}"></a></td>
                    <td class="product-name"><a href="#">${product.name}</a></td>
                    <td class="product-price">${(product.price / 100).toFixed(2)}</td>
                    <td class="add-to-cart"><a href="/panier/${product.id}/ajouter" class="btn-addtocart">Ajouter Au Panier</a></td>
                    <td class="remove-to-wishlist"><a href="/mes-favoris/${product.id}/supprimer"><i class="ti-close"></i></a></td>
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
                ? `/images/products/${product.images[0]}`
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



// export const updateHeaderCart = (cart) => {
//     console.log('Données reçues pour mettre à jour le header :', cart);
//     const cartListElement = document.querySelector('.cart_list');
//     const cartCountElement = document.querySelector('.cart_count');
//     const cart_price_value = document.querySelector('.cart_price_value');
//     const cart_price_taxe = document.querySelector('.cart_price_taxe');
//     const cart_price_ttc = document.querySelector('.cart_price_ttc');

//     if (!cart || !cart.products || cart.products.length === 0) {
//         console.log('Le panier est vide, mise à jour en conséquence.');
//         if (cartListElement) {
//             cartListElement.innerHTML = '<li class="empty-cart">Votre panier est vide.</li>';
//         }

//         if (cart_price_value) cart_price_value.innerHTML = 'Sous Total HT: 0.00 €';
//         if (cart_price_taxe) cart_price_taxe.innerHTML = 'TVA: 0.00 €';
//         if (cart_price_ttc) cart_price_ttc.innerHTML = 'Total TTC: 0.00 €';
//         if (cartCountElement) cartCountElement.textContent = '0';
//         return;
//     }

//     // Mise à jour des totaux globaux
//     if (cart_price_value) {
//         cart_price_value.innerHTML = `Sous Total HT: ${cart.data.subTotalHT.toFixed(2)} €`;
//     }
//     if (cart_price_taxe) {
//         cart_price_taxe.innerHTML = `TVA: ${cart.data.Taxe.toFixed(2)} €`;
//     }
//     if (cart_price_ttc) {
//         cart_price_ttc.innerHTML = `Total TTC: ${cart.data.subTotalTTC.toFixed(2)} €`;
//     }
//     if (cartCountElement) {
//         cartCountElement.textContent = cart.data.cart_count;
//     }

//     // Réinitialisation et mise à jour des produits dans le DOM
//     if (cartListElement) {
//         cartListElement.innerHTML = ''; // Vide la liste avant de la remplir
//         cart.products.forEach(item => {
//             const { variant, quantity } = item;

//             const uniqueKey = `${variant.id}-${variant.size || 'Default'}-${variant.color || 'Default'}`;

//             const product = item.product;
//             const imageUrl = product.images && product.images.length > 0
//                 ? `/images/products/${product.images[0]}`
//                 : '/images/products/default.jpg';

//             const content = `
//                 <li data-variant-key="${uniqueKey}">
//                     <a href="/produit/${product.slug}" class="product-thumbnail">
//                         <img src="${imageUrl}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;">
//                         ${product.name} (${variant.size || 'Default'}, ${variant.color || 'Default'})
//                     </a>
//                     <a href="/mon-panier/${variant.id}/tout-supprimer" class="item_remove"><i class="ion-close"></i></a>
//                     <div class="cart-product-quantity mb-4">
//                         <div class="quantity">
//                             <a href="/mon-panier/${variant.id}/diminuer" class="minus" data-variant-id="${variant.id}">-</a>
//                             <input type="text" value="${quantity}" class="qty" readonly>
//                             <a href="/mon-panier/${variant.id}/ajouter" class="plus" data-variant-id="${variant.id}">+</a>
//                         </div>
//                     </div>
//                     <span class="cart_quantity text-dark qty">${quantity} x <span class="cart_amount">${variant.price.toFixed(2)} €</span></span>
//                 </li>
//             `;
//             cartListElement.insertAdjacentHTML('beforeend', content);
//         });
//     }

//     console.log('Réattachement des événements.');
//     attachQuantityChangeEvents(); // Recharge les événements pour les nouveaux éléments
// }

export const updateHeaderCart = (cart) => {
    console.log('Données reçues pour mettre à jour le header :', cart);

    const cartListElement = document.querySelector('.cart_list');
    const cartCountElement = document.querySelector('.cart_count');
    const cart_price_value = document.querySelector('.cart_price_value');
    const cart_price_taxe = document.querySelector('.cart_price_taxe');
    const cart_price_ttc = document.querySelector('.cart_price_ttc');

    if (!cart || !cart.products || cart.products.length === 0) {
        console.log('Le panier est vide, mise à jour en conséquence.');
        if (cartListElement) {
            cartListElement.innerHTML = '<li class="empty-cart">Votre panier est vide.</li>';
        }

        if (cart_price_value) cart_price_value.innerHTML = '0.00 €';
        if (cart_price_taxe) cart_price_taxe.innerHTML = '0.00 €';
        if (cart_price_ttc) cart_price_ttc.innerHTML = '0.00 €';
        if (cartCountElement) cartCountElement.textContent = '0';
        return;
    }

    // Mise à jour des totaux globaux
    console.log('Mise à jour des totaux globaux.');
    if (cart_price_value) cart_price_value.innerHTML = `${cart.data.subTotalHT.toFixed(2)} €`;
    if (cart_price_taxe) cart_price_taxe.innerHTML = `${cart.data.Taxe.toFixed(2)} €`;
    if (cart_price_ttc) cart_price_ttc.innerHTML = `${cart.data.subTotalTTC.toFixed(2)} €`;
    if (cartCountElement) cartCountElement.textContent = cart.data.cart_count;

    // Mise à jour des produits dans le DOM
    console.log('Mise à jour des produits dans le DOM.');
    cartListElement.innerHTML = '';
    cart.products.forEach(item => {
        const { variant, quantity } = item;

        // Génération d'une clé unique
        const uniqueKey = `${variant.id}-${variant.size}-${variant.color}`;

        console.log('Ajout de l\'élément au DOM avec la clé unique :', uniqueKey);

        const product = item.product;
        const imageUrl = product.images && product.images.length > 0
            ? `/images/products/${product.images[0]}`
            : '/images/products/default.jpg';

        const content = `
            <li data-variant-key="${uniqueKey}">
                <a href="/produit/${product.slug}" class="product-thumbnail">
                    <img src="${imageUrl}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;">
                    ${product.name} (${variant.size}, ${variant.color})
                </a>
                <a href="/mon-panier/${variant.id}/tout-supprimer" class="item_remove"><i class="ion-close"></i></a>
                <div class="cart-product-quantity mb-4">
                    <div class="quantity">
                        <a href="/mon-panier/${variant.id}/diminuer" class="minus" data-variant-id="${variant.id}" data-size="${variant.size}" data-color="${variant.color}">-</a>
                        <input type="text" value="${quantity}" class="qty" readonly>
                        <a href="/mon-panier/${variant.id}/ajouter" class="plus" data-variant-id="${variant.id}" data-size="${variant.size}" data-color="${variant.color}">+</a>
                    </div>
                </div>
                <span class="cart_quantity text-dark qty">${quantity} x <span class="cart_amount">${variant.price.toFixed(2)} €</span></span>
            </li>
        `;

        cartListElement.insertAdjacentHTML('beforeend', content);
    });

    console.log('Réattachement des événements.');
    attachQuantityChangeEvents();
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



// export const updateHeaderCart = (cart) => {
//     const cartListElement = document.querySelector('.cart_list');
//     const cartCountElement = document.querySelector('.cart_count');
//     const cart_price_value = document.querySelector('.cart_price_value');
//     const cart_price_taxe = document.querySelector('.cart_price_taxe');
//     const cart_price_ttc = document.querySelector('.cart_price_ttc');

//     if (!cart || !cart.products || cart.products.length === 0) {
//         console.log('Le panier est vide, mise à jour en conséquence.');
        
//         if (cartListElement) {
//             cartListElement.innerHTML = '<li class="empty-cart">Votre panier est vide.</li>';
//         }
//         if (cartCountElement) {
//             cartCountElement.textContent = '0';
//         }
//         if (cart_price_value) {
//             cart_price_value.innerHTML = '0.00 €';
//         }
//         if (cart_price_taxe) {
//             cart_price_taxe.innerHTML = '0.00 €';
//         }
//         if (cart_price_ttc) {
//             cart_price_ttc.innerHTML = '0.00 €';
//         }

//         return; // Fin de la fonction car le panier est vide
//     }

//     console.log('Mise à jour du header avec les données suivantes:', cart);

//     // Mise à jour des totaux globaux
//     if (cart_price_value) {
//         cart_price_value.innerHTML = `${cart.data.subTotalHT.toFixed(2)} €`;
//     }
//     if (cart_price_taxe) {
//         cart_price_taxe.innerHTML = `${cart.data.Taxe.toFixed(2)} €`;
//     }
//     if (cart_price_ttc) {
//         cart_price_ttc.innerHTML = `${cart.data.subTotalTTC.toFixed(2)} €`;
//     }
//     if (cartCountElement) {
//         cartCountElement.textContent = cart.data.cart_count;
//     }

//     // Réinitialisation et mise à jour des produits dans le DOM
//     if (cartListElement) {
//         cartListElement.innerHTML = ''; // Vide la liste avant de la remplir
//         cart.products.forEach(item => {
//             const { variant, quantity } = item;

//             const existingCartItem = cartListElement.querySelector(`li[data-variant-id="${variant.id}"]`);
//             if (existingCartItem) {
//                 // Mise à jour uniquement de la quantité
//                 const qtyInput = existingCartItem.querySelector('.qty');
//                 if (qtyInput) {
//                     qtyInput.value = quantity;
//                 }
//             } else {
//                 // Ajouter un nouveau produit si non présent
//                 const product = item.product;
//                 const imageUrl = product.images && product.images.length > 0
//                     ? `/images/products/${product.images[0]}`
//                     : '/images/products/default.jpg';

//                 const content = `
//                     <li data-variant-id="${variant.id}">
//                         <a href="/produit/${product.slug}" class="product-thumbnail">
//                             <img src="${imageUrl}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;">
//                             ${product.name}
//                         </a>
//                         <a href="/mon-panier/${variant.id}/tout-supprimer" class="item_remove"><i class="ion-close"></i></a>
//                         <div class="cart-product-quantity mb-4">
//                             <div class="quantity">
//                                 <a href="/mon-panier/${variant.id}/diminuer" class="minus">-</a>
//                                 <input type="text" value="${quantity}" class="qty" readonly>
//                                 <a href="/mon-panier/${variant.id}/ajouter" class="plus">+</a>
//                             </div>
//                         </div>
//                         <span class="cart_quantity text-dark qty" data-variant-id="${variant.id}">${quantity} x <span class="cart_amount">${variant.price.toFixed(2)} €</span></span>
//                     </li>
//                 `;
//                 cartListElement.insertAdjacentHTML('beforeend', content);
//             }
//         });
//     }

//     console.log('Réattachement des événements.');
//     addEventListenerToCartLinks(); // Recharge les événements pour les nouveaux éléments
// };

// export const updateHeaderCart = (cart) => {
//     const cartListElement = document.querySelector('.cart_list');
//     const cartCountElement = document.querySelector('.cart_count');
//     const cart_price_value = document.querySelector('.cart_price_value');
//     const cart_price_taxe = document.querySelector('.cart_price_taxe');
//     const cart_price_ttc = document.querySelector('.cart_price_ttc');

//     if (!cart || !cart.products || cart.products.length === 0) {
//         console.log('Le panier est vide, mise à jour en conséquence.');

//         if (cartListElement) {
//             cartListElement.innerHTML = '<li class="empty-cart">Votre panier est vide.</li>';
//         }

//         if (cart_price_value) cart_price_value.innerHTML = 'Sous Total HT: 0.00 €';
//         if (cart_price_taxe) cart_price_taxe.innerHTML = 'TVA: 0.00 €';
//         if (cart_price_ttc) cart_price_ttc.innerHTML = 'Total TTC: 0.00 €';
//         if (cartCountElement) cartCountElement.textContent = '0';

//         return;
//     }

//     console.log('Mise à jour du header avec les données suivantes:', cart);

//     // Mise à jour des totaux globaux
//     if (cart_price_value) {
//         cart_price_value.innerHTML = `Sous Total HT: ${cart.data.subTotalHT.toFixed(2)} €`;
//     }
//     if (cart_price_taxe) {
//         cart_price_taxe.innerHTML = `TVA: ${cart.data.Taxe.toFixed(2)} €`;
//     }
//     if (cart_price_ttc) {
//         cart_price_ttc.innerHTML = `Total TTC: ${cart.data.subTotalTTC.toFixed(2)} €`;
//     }
//     if (cartCountElement) {
//         cartCountElement.textContent = cart.data.cart_count;
//     }

//     // Réinitialisation et mise à jour des produits dans le DOM
//     if (cartListElement) {
//         cartListElement.innerHTML = ''; // Vide la liste avant de la remplir
//         cart.products.forEach(item => {
//             const { variant, quantity } = item;

//             // Construire un identifiant unique pour la variante
//             const uniqueKey = `${variant.id}-${variant.size || 'default'}-${variant.color || 'default'}`;

//             const product = item.product;
//             const imageUrl = product.images && product.images.length > 0
//                 ? `/images/products/${product.images[0]}`
//                 : '/images/products/default.jpg';

//             const content = `
//                 <li data-variant-key="${uniqueKey}">
//                     <a href="/produit/${product.slug}" class="product-thumbnail">
//                         <img src="${imageUrl}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;">
//                         ${product.name} (${variant.size || 'Default'}, ${variant.color || 'Default'})
//                     </a>
//                     <a href="/mon-panier/${variant.id}/tout-supprimer" class="item_remove"><i class="ion-close"></i></a>
//                     <div class="cart-product-quantity mb-4">
//                         <div class="quantity">
//                             <a href="/mon-panier/${variant.id}/diminuer" class="minus" data-variant-id="${variant.id}">-</a>
//                             <input type="text" value="${quantity}" class="qty" readonly>
//                             <a href="/mon-panier/${variant.id}/ajouter" class="plus" data-variant-id="${variant.id}">+</a>
//                         </div>
//                     </div>
//                     <span class="cart_quantity text-dark qty">${quantity} x <span class="cart_amount">${variant.price.toFixed(2)} €</span></span>
//                 </li>
//             `;
//             cartListElement.insertAdjacentHTML('beforeend', content);
//         });
//     }

//     console.log('Réattachement des événements.');
//     addEventListenerToCartLinks(); // Recharge les événements pour les nouveaux éléments
// };




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
console.log('yilmaz')

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

    plusButtons.forEach(button => {
        button.addEventListener('click', event => handleQuantityChange(event, 'increase'));
    });

    minusButtons.forEach(button => {
        button.addEventListener('click', event => handleQuantityChange(event, 'decrease'));
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

document.querySelectorAll('.btn-addtocart').forEach(button => {
    button.addEventListener('click', async (event) => {
        event.preventDefault();

        const variantId = button.dataset.variantId;
        const quantityInput = document.querySelector('input#quantity'); // Sélectionnez l'élément quantité
        const quantity = parseInt(quantityInput?.value, 10) || 1; // Valeur saisie ou 1 par défaut
        const size = document.querySelector('select[name="size"]').value || 'DefaultSize';
        const color = document.querySelector('select[name="color"]').value || 'DefaultColor';

        console.log(`Quantité sélectionnée : ${quantity}`);
        console.log(`Taille sélectionnée : ${size}`);
        console.log(`Couleur sélectionnée : ${color}`);

        const url = `/panier/${variantId}/ajouter/${quantity}?size=${size}&color=${color}`;

        try {
            const response = await fetch(url, { method: 'POST' });
            if (!response.ok) {
                throw new Error('Erreur lors de l\'ajout au panier');
            }

            const data = await response.json();
            if (data.products) {
                updateHeaderCart(data); // Mettre à jour dynamiquement le panier
                console.log('Panier mis à jour :', data);
            }
        } catch (error) {
            console.error('Erreur :', error.message);
        }
    });
});





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

