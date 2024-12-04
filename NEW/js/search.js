// PRODUCTS Search Functionality
const searchInput = document.getElementById('searchInput');
const productCards = document.querySelectorAll('.product-card');
const noProductFound = document.getElementById('noProductFound');

    searchInput.addEventListener('input', function() {
        const searchValue = searchInput.value.toLowerCase();
        let anyCardVisible = false;

        productCards.forEach(card => {
            const brandElement = card.querySelector('h4');
            const nameElement = card.querySelector('p');
            const price = card.getAttribute('data-price');

            const brand = brandElement ? brandElement.textContent.toLowerCase() : '';
            const name = nameElement ? nameElement.textContent.toLowerCase() : '';
            const priceText = price ? price.toLowerCase() : '';

            if (brand.includes(searchValue) || name.includes(searchValue) || priceText.includes(searchValue)) {
                card.style.display = '';
                anyCardVisible = true;
            } else {
                card.style.display = 'none';
            }
        });
        noProductFound.style.display = anyCardVisible ? 'none' : 'block';
    });

// STOCKS Search Functionality
const searchStockInput = document.getElementById('searchInput');
const stockCards = document.querySelectorAll('.stock-card');
const noStockFound = document.getElementById('noStockFound');

    searchStockInput.addEventListener('input', function() {
    const searchValue = searchStockInput.value.toLowerCase();
    let anyCardVisible = false;

        stockCards.forEach(card => {
            const brandElement = card.querySelector('h4');
            const nameElement = card.querySelector('p');
            const stockQuantity = card.getAttribute('data-stock');

            const brand = brandElement ? brandElement.textContent.toLowerCase() : '';
            const name = nameElement ? nameElement.textContent.toLowerCase() : '';
            const stockText = stockQuantity ? stockQuantity.toLowerCase() : '';

            if (brand.includes(searchValue) || name.includes(searchValue) || stockText.includes(searchValue)) {
                card.style.display = '';
                anyCardVisible = true;
            } else {
                card.style.display = 'none';
            }
        });

    noStockFound.style.display = anyCardVisible ? 'none' : 'block';
});