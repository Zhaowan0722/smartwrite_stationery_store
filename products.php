<?php
require_once "db.php";
require_once "helpers.php";

ensure_session();

/* temporary user for testing */
$_SESSION["user_id"] = 1;

/* get all products */
$result = mysqli_query($conn,"SELECT * FROM products");
?>

<!DOCTYPE html>
<html>

<head>

<title>Stationery Products</title>

<style>

body{
    font-family:Arial;
    background:#f5f5f5;
    margin:0;
}

.container{
    width:1100px;
    margin:auto;
}

h2{
    padding:20px 0;
}

/* product grid */

.products{
    display:flex;
    gap:25px;
    flex-wrap:wrap;
}

/* product card */

.card{
    background:white;
    width:220px;
    padding:15px;
    border-radius:8px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
    text-align:center;
}

.card img{
    width:150px;
    height:150px;
    object-fit:cover;
}

.card h3{
    margin:10px 0 5px;
}

.price{
    color:#e63946;
    font-weight:bold;
}

.btn{
    display:inline-block;
    margin-top:10px;
    padding:6px 12px;
    background:#007bff;
    color:white;
    text-decoration:none;
    border-radius:4px;
}

.btn:hover{
    background:#0056b3;
}

</style>

</head>

<body>

<div class="container">

<h2>Stationery Products</h2>

<div class="products">

<?php while($p = mysqli_fetch_assoc($result)){ ?>

<div class="card">

<img src="<?php echo $p["image"]; ?>">

<h3><?php echo $p["product_name"]; ?></h3>

<p><?php echo $p["category"]; ?></p>

<p class="price">
RM <?php echo number_format($p["price"],2); ?>
</p>

<a class="btn"
href="product_detail.php?id=<?php echo $p["product_id"]; ?>">
View Product
</a>

</div>

<?php } ?>

</div>

</div>

</body>
</html>