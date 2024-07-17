import { initCart, displayWishlist} from './library.js';

window.onload = () =>{
    let mainContent

    mainContent = document.querySelector('.wishlist_content')
    let wishlist = JSON.parse(mainContent?.dataset?.wishlist || false)
    
    displayWishlist(wishlist)
    
    mainContent = document.querySelector('.cart_content')
    let cart = JSON.parse(mainContent?.dataset?.cart || false)
    
    initCart(cart)
}




/*! Pour le menu  */

// Écouteur d'événements sur le clic du body pour fermer le menu
document.body.addEventListener('click', function (e) {
    var menu = document.getElementById('navbarSupportedContent');
    var menuButton = document.getElementById('menu-btns');
    var cartButton = document.getElementById('cart-btn');

    // Vérifiez si le clic est en dehors du menu, du bouton de menu, et du bouton de panier
    if (!menu.contains(e.target) && !menuButton.contains(e.target) && !cartButton.contains(e.target)) {
        // Fermez le menu ici si nécessaire
        closeMenu(menu);
    }
});

// Écouteur d'événements sur le clic du bouton de panier pour fermer le menu
var cartButton = document.getElementById('cart-btn');
cartButton.addEventListener('click', function () {
    var menu = document.getElementById('navbarSupportedContent');

    // Fermez le menu ici si nécessaire
    closeMenu(menu);
});

function closeMenu(menu) {
    // Vérifiez si le menu est ouvert
    var isMenuOpen = menu.classList.contains('show');
    if (isMenuOpen) {
        // Pour Bootstrap 5
        var bsCollapse = new bootstrap.Collapse(menu, {
            toggle: false
        });
        bsCollapse.hide();
    }
}

/*! Pour le panier */

document.addEventListener('DOMContentLoaded', function () {
    // Empêche la fermeture du panier lors du clic sur ses éléments internes
    const cartItems = document.querySelectorAll('.cart_box, .cart_box *');
    cartItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});

/*! Pour pouvoir cliquer sur l'image et aller a la page de prduit */



console.log('cart')

