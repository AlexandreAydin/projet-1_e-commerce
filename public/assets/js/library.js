export const formatPrice = (price) => {
    return Intl.NumberFormat('en-US', { style: 'currency', currency: 'EUR' })
        .format(price);
}

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


export const manageWishListLink = async (event) => {
    event.preventDefault();
    const link = event.target.closest('a');
    
    if (!link) return;

    const requestUrl = link.href;
    console.log(requestUrl);

    if (link.classList.contains('view-details')) {
        window.location.href = requestUrl;
        return;
    }

    if (requestUrl.includes('/mes-favoris/supprimer/')) {
        try {
            // Suppose que fetchDataWithMethod envoie une requête DELETE
            const data = await fetchDataWithMethod(requestUrl, 'DELETE');
            if (data && data.success) {
                const tableRow = link.closest('tr');
                tableRow.parentNode.removeChild(tableRow);
                addFlashMessage(`Product (${data.product.name}) removed from wish list!`, "danger");
            } else if (data && data.message) {
                console.error('Erreur lors de la suppression du produit des favoris:', data.message);
            } else {
                console.error('Erreur inconnue lors de la suppression du produit des favoris');
            }
        } catch (err) {
            console.error('Erreur lors de l’envoi de la requête DELETE:', err);
        }
        return;
    }

    // Si vous atteignez cette partie, cela signifie que l'URL ne contient pas '/mes-favoris/supprimer/'
    // et que vous essayez de faire une autre opération.
    try {
        let wishlist = await fetchData(requestUrl);
        console.log(wishlist);
        // Votre logique supplémentaire ici
        initCart();
        updateHeaderCart();
        displayWishlist(wishlist);
    } catch (err) {
        console.error('Erreur lors de l’envoi de la requête GET:', err);
    }
}

async function fetchDataWithMethod(requestUrl, method = 'GET') {
    try {
                const response = await fetch(requestUrl, {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });
        
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return await response.json();
            } catch (error) {
                console.error(`Erreur lors de la requête (${method}):`, error.message);
                return null;
            }
}

export const addWishListEventListenerToLink = () => {
    let links = document.querySelectorAll(".add-to-wishlist, .wishlist_table .remove-to-wishlist");
    links.forEach(link => {
        link.addEventListener("click", manageWishListLink);
    });
}


function removeProductFromWishlist(productId, element) {
    fetch(`/mes-favoris/${productId}/supprimer`, {
                method: 'DELETE', 
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Réponse du réseau non OK.');
                }
                return response.json();
            })
        
            .catch(error => {
                console.error('Erreur lors de la suppression du produit des favoris:', error.message);
            });
}

export const displayWishlist = (wishlist = null) => {
    addWishListEventListenerToLink();
    
    if (!wishlist) return;
    
    let tbody = document.querySelector('.wishlist_table tbody');

    if (tbody) {
        tbody.innerHTML = "";
        wishlist.forEach((product) => {
            const imageUrl = product.images 
            ? `/images/products/${product.images}` 
            : '/images/placeholder-image.jpg';

            let content = `
            <tr>
            <td class="product-thumbnail">
            <a href="#">
                <img src="${imageUrl}" alt="${product.name}">
            </a>
        </td>
        <td class="product-name" data-title="Product"><a href="#">${product.name}</a></td>
        <td class="product-price" data-title="Price">${(product.price / 100).toFixed(2)}</td>
        <td data-title="Stock Status" class="product-stock-status">
        ${
            product.quantity > 10 
            ? '<span class="badge badge-pill badge-success">En Stock</span>'
            : product.quantity > 0 
                ? `Il reste plus que ${product.quantity} articles dans le stock`
                : 'Ce produit n\'est plus disponible pour le moment'
        }
        </td>
        <td class="add-to-cart ">
        <a href="/panier/${product.id}/ajouter" class="btn btn-fill-out btn-addtocart">
            <i class="icon-basket-loaded"></i> Ajouter Au Panier
        </a>
        </td>
        <td class="remove-to-wishlist" data-title="Remove">
            <a href="/mes-favoris/${product.id}/supprimer"><i class="ti-close"></i></a>
        </td>
    </tr>
            </tr>
            `;
            tbody.innerHTML += content;
        });
    }

    addWishListEventListenerToLink();
}

const mainContent = document.querySelector('.section');
const tbody = document.querySelector('tbody');
const cartSubtotalElement = document.querySelector('.cart_subtotalHT');
const cartTaxeElement = document.querySelector('.cart_taxe');
const cartTotalElement = document.querySelector('.cart_subtotalTTC');

let cart = mainContent && mainContent.dataset.cart ? JSON.parse(mainContent.dataset.cart) : {};

async function manageLink(event) {
    event.preventDefault();
    const link = event.target.closest('a');
    if (link && link.classList.contains('view-details')) {
        window.location.href = link.href;
        return;
    }
    if (link && link.href) {
        cart = await fetchData(link.href);
        initCart();
        updateHeaderCart();
    }
}

function addEventListenerToLink() {
    const links = document.querySelectorAll('.shop_cart_table tbody a, li.add-to-cart a, li add-to-wishlist, a.item_remove,a.minus, a.plus, a.btn-addtocart, a.ion-close, .view-details');
    links.forEach(link => link.addEventListener('click', manageLink));
}

export const initCart = (cart = null) => {
    if (!cart || !cart.products) {
        addEventListenerToLink();
        return;
    }

    if (tbody) {
        tbody.innerHTML = '';
        const cartDataElement = document.querySelector('#cart-data');
        if (cartDataElement) {
            cart.products.forEach(item => {
                const { product, quantity } = item;
                const imageUrl = product.images ? `images/products/${product.images}` : 'images/placeholder-image.jpg';
                const deletePath = generatePath(cartDataElement.getAttribute('data-delete-path'), product.id);
                const addPath = generatePath(cartDataElement.getAttribute('data-add-path'), product.id);
                const deleteAllPath = generatePath(cartDataElement.getAttribute('data-delete-all-path'), product.id);
                
                const content = `
                                <tr>
                                    <td class="product-thumbnail">
                                        <a href="#"><img src="${imageUrl}" alt="${product.name}"></a>
                                    </td>
                                    <td class="product-name" data-title="Product"><a href="#">${product.name}</a></td>
                                    <td class="product-price" data-title="Price">${(product.price / 100).toFixed(2)}</td>
                                    <td class="product-quantity" data-title="Quantity">
                                        <div class="quantity">
                                            <a href="${deletePath}" type="button" class="minus"> - </a>
                                            <input type="text" name="quantity" value="${quantity}" title="Qty" class="qty" size="4">
                                            ${quantity ? `<a href="${addPath}" type="button" class="plus"> + </a>` : ''}
                                        </div>
                                    </td>
                                    <td class="product-subtotal" data-title="Total">${(quantity * product.price / 100).toFixed(2)}</td>
                                    <td class="product-remove" data-title="Remove">
                                        <a href="${deleteAllPath}"><i class="ti-close"></i></a>
                                    </td>
                                </tr>
                            `;
                    
                            tbody.insertAdjacentHTML('beforeend', content);
                        });
            }
        }

    // Mise à jour des totaux du panier
    if (cartSubtotalElement && cart.data && cart.data.subTotalHT) {
        cartSubtotalElement.innerHTML = `${cart.data.subTotalHT.toFixed(2)}`;
    }
    if (cartTaxeElement && cart.data && cart.data.Taxe) {
        cartTaxeElement.innerHTML = `${cart.data.Taxe.toFixed(2)}`;
    }
    if (cartTotalElement && cart.data && cart.data.subTotalTTC) {
        cartTotalElement.innerHTML = `${cart.data.subTotalTTC.toFixed(2)}`;
    }

    addEventListenerToLink();
}

async function updateQuantityOnServer(productId, newQuantity) {
    const url = `/mon-panier/${productId}`; // Supposition pour l'URL, à adapter en fonction de votre backend
    const response = await fetch(url, {
        method: 'POST', // ou 'PUT' selon votre API
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ quantity: newQuantity })
    });

    if (!response.ok) {
        throw new Error('Erreur réseau lors de la mise à jour.');
    }
    return response.json();
}

async function updateHeaderCart() {
    let cart;
    try {
        cart = await fetchData("/mon-panier/obtenir");
        
        if (!cart || !cart.products || !cart.data) {
            throw new Error("Le panier ou les éléments du panier ne sont pas définis ou ne sont pas dans le format attendu.");
        }

        const cart_count = document.querySelector('.cart_count');
        const cart_list = document.querySelector('.cart_list');
        const cart_price_value = document.querySelector('.cart_price_value');
        const cart_price_taxe = document.querySelector('.cart_price_taxe');
        const cart_price_ttc = document.querySelector('.cart_price_ttc');

        cart_count.innerHTML = cart.data.cart_count;
        cart_price_value.innerHTML = `${cart.data.subTotalHT.toFixed(2)} €`;
        cart_price_taxe.innerHTML = `${cart.data.Taxe.toFixed(2)} €`;
        cart_price_ttc.innerHTML = `${cart.data.subTotalTTC.toFixed(2)} €`;

        let contenuDuPanier = '';
        cart.products.forEach(item => {
            const { product, quantity } = item;

            const imageUrl = product.images 
                ? `/images/products/${product.images}` 
                : '/images/placeholder-image.jpg';

            contenuDuPanier += `
                <li data-product-id="${product.id}">
                    <a href="/produit/${product.slug}" class="product-thumbnail">
                        <img src="${imageUrl}" alt="${product.name}">${product.name}
                    </a>
                    <a href="/mon-panier/${product.id}/tout-supprimer" class="item_remove"><i class="ion-close"></i></a>
                    <div class="cart-product-quantity">
                        <div class="quantity">
                            <a href="/mon-panier/${product.id}/diminuer" type="button" class="minus"> - </a>
                            <input type="text" name="quantity" value="${quantity}" title="Qty" size="4" class="qty">
                            <a  href="/panier/${product.id}/ajouter" type="button" class="plus"> + </a>
                        </div>
                    </div>
                    <span class="cart_quantity text-dark"> ${quantity} x <span class="cart_amount">${(product.price / 100).toFixed(2)} €</span>
                </li>
            `;
        });
        cart_list.innerHTML = contenuDuPanier;
    } catch (error) {
        console.error(error);
    }
    addEventListenerToLink();
}

async function updateCart(url) {
    try {
        const response = await fetch(url, { method: 'POST' });
        if (!response.ok) {
            throw new Error("Erreur lors de la mise à jour du panier.");
        }
        updateHeaderCart(); // Mise à jour du panier dans le DOM
    } catch (error) {
        console.error('Erreur lors de la mise à jour du panier:', error);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Écouteur d'événement pour les liens de suppression des produits des favoris
    let removeLinks = document.querySelectorAll('.remove-to-wishlist a');
    removeLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault(); // Empêcher la navigation par défaut
            let productId = this.href.split('/').slice(-2)[0]; // Récupérer l'ID du produit à partir de l'URL
            removeProductFromWishlist(productId, this);
        });
    });

    updateHeaderCart();
    initCart();
});

// document.addEventListener('click', async function(event) {
//     if (event.target && event.target.classList.contains('plus')) {
//         const productId = event.target.closest('li').getAttribute('data-product-id');
//         await updateCart(`/panier/${productId}/ajouter`);
//     }

//     if (event.target && event.target.classList.contains('minus')) {
//         const productId = event.target.closest('li').getAttribute('data-product-id');
//         await updateCart(`/mon-panier/${productId}/diminuer`);
//     }
// });
