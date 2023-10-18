
import { initCart, displayWishlist} from './library.js';

window.onload = () =>{
    let mainContent
    
    console.log("wishlist");
    mainContent = document.querySelector('.wishlist_content')
    let wishlist = JSON.parse(mainContent?.dataset?.wishlist || false)
    
    displayWishlist(wishlist)
    
    console.log("cart");
    mainContent = document.querySelector('.cart_content')
    let cart = JSON.parse(mainContent?.dataset?.cart || false)
    
    initCart(cart)
}
