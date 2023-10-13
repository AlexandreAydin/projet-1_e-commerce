// import '../css/app.scss';

// console.log('test')

// // const { async } = require("regenerator-runtime");

// // cart.js 
// const mainContent = document.querySelector('.section');
// const tbody = document.querySelector('tbody');
// const cartSubtotalElement = document.querySelector('.cart_subtotalHT');
// const cartTaxeElement = document.querySelector('.cart_taxe');
// const cartTotalElement = document.querySelector('.cart_subtotalTTC');

// let cart = JSON.parse(mainContent?.dataset?.cart || false);

// function generatePath(pathTemplate, productId) {
// return pathTemplate.replace('element.product.id', productId);
// }
// const fetchData = async (requestUrl) => {
// const respons = await fetch(requestUrl)
// return await respons.json()
// }
// const manageLink = async (event) => {
// event.preventDefault();
// const link = event.target.href ? event.target : event.target.parentNode
// const requestUrl =link.href
// cart = await fetchData(requestUrl);
// initCart()
// updateHeaderCart()
    
// }

// const addEventListenerToLink = () => {
//     const links = document.querySelectorAll('tbody a');
//     links.forEach((link) => {
//         link.addEventListener('click', manageLink);
//     });
//     const add_to_cart_links = document.querySelectorAll('li.add-to-cart a, a.item_remove');
//     add_to_cart_links.forEach((link) => {
//         link.addEventListener('click', manageLink);
//     });
// }

// const initCart = () => {
//     if(!cart){
//         addEventListenerToLink();
//        return 
//     }

//     if(tbody){
//         tbody.innerHTML = '';  // Empty existing content in tbody

//         cart.products.forEach((item) => {
//             const { product, quantity } = item;
    
//             const imageUrl = product.images 
//                 ? `images/products/${product.images}` 
//                 : 'images/placeholder-image.jpg';
    
//             const deletePath = generatePath(document.querySelector('#cart-data').getAttribute('data-delete-path'), product.id);
//             const addPath = generatePath(document.querySelector('#cart-data').getAttribute('data-add-path'), product.id);
//             const deleteAllPath = generatePath(document.querySelector('#cart-data').getAttribute('data-delete-all-path'), product.id);
    
//             const content = `
//                 <tr>
//                     <td class="product-thumbnail">
//                         <a href="#"><img src="${imageUrl}" alt="${product.name}"></a>
//                     </td>
//                     <td class="product-name" data-title="Product"><a href="#">${product.name}</a></td>
//                     <td class="product-price" data-title="Price">${(product.price / 100).toFixed(2)}</td>
//                     <td class="product-quantity" data-title="Quantity">
//                         <div class="quantity">
//                             <a href="${deletePath}" type="button" class="minus"> - </a>
//                             <input type="text" name="quantity" value="${quantity}" title="Qty" class="qty" size="4">
//                             ${quantity ? `<a href="${addPath}" type="button" class="plus"> + </a>` : ''}
//                         </div>
//                     </td>
//                     <td class="product-subtotal" data-title="Total">${(quantity * product.price / 100).toFixed(2)}</td>
//                     <td class="product-remove" data-title="Remove">
//                         <a href="${deleteAllPath}"><i class="ti-close"></i></a>
//                     </td>
//                 </tr>
//             `;
    
//             tbody.insertAdjacentHTML('beforeend', content);
//         });
    
//         // Display Cart Totals 
//         cartSubtotalElement.innerHTML = `${cart.data.subTotalHT.toFixed(2)}`;
//         cartTaxeElement.innerHTML = `${cart.data.Taxe}`;
//         cartTotalElement.innerHTML = `${cart.data.subTotalTTC.toFixed(2)}`;
//     }

//     addEventListenerToLink();
// }

    
//     async function updateHeaderCart() {
//         let cart;
        
//         try {
//             cart = await fetchData("/mon-panier/obtenir");
            
//             if (!cart || !cart.products || !cart.data) {
//                 throw new Error("Le panier ou les éléments du panier ne sont pas définis ou ne sont pas dans le format attendu.");
//             }
    
//             const cart_count = document.querySelector('.cart_count');
//             const cart_list = document.querySelector('.cart_list');
//             const cart_price_value = document.querySelector('.cart_price_value');

    
//             cart_count.innerHTML = cart.data.cart_count;
//             cart_price_value.innerHTML = `${cart.data.subTotalHT.toFixed(2)}`;

    
//             let contenuDuPanier = '';
    
//             cart.products.forEach(item => {
//                 const { product, quantity, subTotalTTC } = item;
//                 const id = product.id;
                
//                 const imageUrl = product.images 
//                     ? `/images/products/${product.images}` 
//                     : '/images/placeholder-image.jpg';

                
//                 contenuDuPanier += `
//                     <li>
//                         <a href="/produit/${product.slug}" class="product-thumbnail">
//                             <img src="${imageUrl}" alt="${product.name}">${product.name}
//                         </a>
//                         <a href="/mon-panier/${id}/tout-supprimer" class="item_remove"><i class="ion-close"></i></a>
//                         <span class="cart_quantity text-dark"> ${quantity} x <span class="cart_amount">${(product.price / 100).toFixed(2)} €</span>
//                     </li>
//                 `;

//             });
            
    
//             cart_list.innerHTML = contenuDuPanier;
    
//         } catch (error) {
//             console.error(error);
//         }
//         addEventListenerToLink();
//     }
//     initCart()
//     updateHeaderCart()
    
//     document.addEventListener('DOMContentLoaded', () => {
//         updateHeaderCart();
//     });
