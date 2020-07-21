<?php
require_once __DIR__ . '/autoload.php';

/** @var PDO */
$pdo = require_once __DIR__ . '/pdo.php';

$faker = Faker\Factory::create();
$faker->seed(719889473);

$orderCount = $faker->numberBetween(100, 500);
$productCount = $faker->numberBetween(10, 25);

$pdo->query('TRUNCATE products');
$pdo->query('TRUNCATE orders');
$pdo->query('TRUNCATE line_items');

$statement = $pdo->prepare(<<<SQL
    INSERT INTO products (
        code,
        name,
        description,
        unit_price,
        created_at,
        updated_at,
        deleted_at
    ) VALUES (
        :code,
        :name,
        :description,
        :unit_price,
        :created_at,
        NULL,
        NULL
    );
SQL);
$products = [];
for ($p = 0; $p < $productCount; $p++) {
    $product = [
        'code' => $faker->ean13,
        'name' => ucfirst($faker->word),
        'description' => implode(PHP_EOL . PHP_EOL, $faker->paragraphs(2)),
        'unit_price' => $faker->randomFloat(2, 1, 100),
        'created_at' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d H:i:s'),
    ];
    $statement->execute($product);
    $products[$pdo->lastInsertId()] = $product;
}

$orderStatement = $pdo->prepare(<<<SQL
    INSERT INTO orders (
        status,
        first_name,
        last_name,
        email_address,
        total,
        created_at,
        updated_at
    ) VALUES (
        :status,
        :first_name,
        :last_name,
        :email_address,
        :total,
        :created_at,
        NULL
    );
SQL);

$lineItemStatement = $pdo->prepare(<<<SQL
    INSERT INTO line_items (
        order_id,
        product_id,
        quantity
    ) VALUES (
        :order_id,
        :product_id,
        :quantity
    );
SQL);
for ($o = 0; $o < $orderCount; $o++) {
    $lineItemCount = $faker->numberBetween(1, 15);

    $total = 0;
    $lineItems = [];
    for ($l = 0; $l < $lineItemCount; $l++) {
        $lineItem = [
            'order_id' => null,
            'product_id' => $faker->randomKey($products),
            'quantity' => $faker->numberBetween(1, 10),
        ];
        $lineItems[] = $lineItem;
        $total += $lineItem['quantity'] * $products[$lineItem['product_id']]['unit_price'];
    }
    $orderStatement->execute([
        'status' => $faker->randomElement(['confirmed', 'completed', 'canceled']),
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email_address' => $faker->safeEmail,
        'total' => $total,
        'created_at' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d H:i:s'),
    ]);
    $orderId = $pdo->lastInsertId();
    foreach ($lineItems as $lineItem) {
        $lineItem['order_id'] = $orderId;
        $lineItemStatement->execute($lineItem);
    }
}
