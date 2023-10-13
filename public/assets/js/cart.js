const mainContent = document.querySelector('.section');
const tbody = document.querySelector('tbody');
const cartSubtotalElement = document.querySelector('.cart_subtotalHT');
const cartTaxeElement = document.querySelector('.cart_taxe');
const cartTotalElement = document.querySelector('.cart_subtotalTTC');

let cart = mainContent && mainContent.dataset.cart ? JSON.parse(mainContent.dataset.cart) : {};

async function fetchData(requestUrl) {
    try {
        const response = await fetch(requestUrl);
        return await response.json();
    } catch (error) {
        console.error('Erreur lors de la récupération des données:', error);
        return null;
    }
}

function generatePath(pathTemplate, productId) {
    return pathTemplate ? pathTemplate.replace('element.product.id', productId) : '';
}

async function manageLink(event) {
    event.preventDefault();
    const link = event.target.closest('a');
    if (link && link.href) {
        cart = await fetchData(link.href);
        initCart();
        updateHeaderCart();
    }
}

function addEventListenerToLink() {
    const links = document.querySelectorAll('tbody a, li.add-to-cart a, a.item_remove, a.btn-addtocart, a.ion-close');
    links.forEach(link => link.addEventListener('click', manageLink));
}

function initCart() {
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

                let minusButtonContent;

                if (quantity === 1) {
                    minusButtonContent = `<i class="fa-thin fa-trash"></i>`;
                } else {
                    minusButtonContent = `<a href="${deletePath}" type="button" class="minus"> - </a>`;
                }
                
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
        cartTaxeElement.innerHTML = `${cart.data.Taxe}`;
    }
    if (cartTotalElement && cart.data && cart.data.subTotalTTC) {
        cartTotalElement.innerHTML = `${cart.data.subTotalTTC.toFixed(2)}`;
    }

    addEventListenerToLink();
}


async function fetchData(url) {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error('Erreur réseau lors de la récupération des données.');
    }
    return response.json();
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


async function fetchData(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error("Erreur réseau lors de la récupération des données.");
        }
        return await response.json();
    } catch (error) {
        console.error("Erreur de récupération des données :", error);
    }
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

        cart_count.innerHTML = cart.data.cart_count;
        cart_price_value.innerHTML = `${cart.data.subTotalHT.toFixed(2)} €`;

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
                            <a type="button" class="minus"> - </a>
                            <input type="text" name="quantity" value="${quantity}" title="Qty" size="4" class="qty">
                            <a type="button" class="plus"> + </a>
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

document.addEventListener('click', async function(event) {
    if (event.target && event.target.classList.contains('plus')) {
        const productId = event.target.closest('li').getAttribute('data-product-id');
        await updateCart(`/panier/${productId}/ajouter`);
    }

    if (event.target && event.target.classList.contains('minus')) {
        const productId = event.target.closest('li').getAttribute('data-product-id');
        await updateCart(`/mon-panier/${productId}/diminuer`);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    updateHeaderCart();
    initCart();
});


