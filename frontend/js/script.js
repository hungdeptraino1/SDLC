function loadProducts(search = '', category = '', minPrice = '', maxPrice = '') {
    fetch(`../backend/products.php?search=${search}&category=${category}&min_price=${minPrice}&max_price=${maxPrice}`)
        .then(response => response.json())
        .then(products => {
            const productList = document.getElementById('product-list');
            productList.innerHTML = '';
            products.forEach(product => {
                productList.innerHTML += `
                    <div class="col-md-4 col-sm-6">
                        <div class="card mb-4">
                            <img src="../uploads/${product.image}" class="card-img-top" alt="${product.name}">
                            <div class="card-body">
                                <h5 class="card-title">${product.name}</h5>
                                <p class="card-text">${product.price} VND</p>
                                <button class="btn btn-primary" onclick="addToCart(${product.id})">Thêm vào giỏ</button>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
}

loadProducts();

document.getElementById('search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const search = document.getElementById('search').value;
    const category = document.getElementById('category').value;
    const minPrice = document.getElementById('min-price').value;
    const maxPrice = document.getElementById('max-price').value;
    loadProducts(search, category, minPrice, maxPrice);
});

function addToCart(productId) {
    fetch('../backend/cart.php', {
        method: 'POST',
        body: JSON.stringify({ product_id: productId }),
        headers: { 'Content-Type': 'application/json' }
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              alert('Đã thêm vào giỏ hàng!');
          } else {
              alert('Vui lòng đăng nhập!');
          }
      });
}