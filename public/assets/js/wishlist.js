import {
    displayWishlist,
    addWishListEventListenerToLink
     } from './library.js';

window.onload = () => {

    let mainContent = document.querySelector('.wishlist_container')

    let wishlist = JSON.parse(mainContent?.dataset?.wishlist || false)

    addWishListEventListenerToLink()

    displayWishlist(wishlist)

}