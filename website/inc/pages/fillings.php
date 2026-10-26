<?php
// From the "Cake Type & Fillings" page of the current lasemcakes.ca.
$menu = [
    'Types of custom cakes' => ['European Sponge Cake (Vanilla or Chocolate)', 'Sfoglia (Puff Pastry, eggless)', 'Pound Cake', 'Red Velvet', 'Sacher Sponge (Devil’s Food)', 'Almond Sponge', 'Carrot Cake', 'Gluten Free Sponge', 'Almond Meringue', 'Cheesecake (NY Style)', 'Ice Cream'],
    'Cake fillings' => ['Vanilla Custard', 'Chocolate Custard', 'Chantilly Cream', 'Hazelnut', 'Bacio', 'White Bacio', 'Frutto di Bosco (Wildberry)', 'Cappuccino', 'Coffee Mocha', 'Strawberry Cream', 'Cream Cheese Icing', 'Tiramisu Cream', 'Torrone (Almond)', 'Lemon', 'Mango', 'Grand Marnier', 'Amaretto', 'Banana', 'Zabaglione', 'Fresh Strawberries', 'Fresh Kiwi', 'Peach Slices', 'Pineapple Chunks', 'Mixed Fruit'],
    'Icings' => ['Whipped Cream', 'Butter Cream', 'Chocolate Ganache', 'Rolled Fondant'],
    'Shapes' => ['Round', 'Square', 'Hexagon', 'Heart', 'Petal'],
    'Sizes' => ['6″', '8″', '10″', '12″', '14″', '16″', '18″'],
];
page_start([
    'title'       => 'Cake Types & Fillings',
    'path'        => 'cake-type-fillings/',
    'description' => 'Choose your La Sem custom cake: European sponge, sfoglia, red velvet, almond and more, with 24 fillings, four icings, and round, square, hexagon, heart or petal shapes from 6″ to 18″.',
]);
page_banner('Cake types & fillings', 'Design your own', 'Every custom cake starts with these choices. Mix and match, or ask us what goes well together.', ['Bakery' => url('bakery/'), 'Cake types & fillings' => url('cake-type-fillings/')]);
?>
<section class="section">
  <div class="wrap fill-grid">
<?php foreach ($menu as $heading => $items): ?>
    <div class="fill-card">
      <h2><?= e($heading) ?></h2>
      <p class="chips"><?php foreach ($items as $it): ?><span><?= e($it) ?></span><?php endforeach; ?></p>
    </div>
<?php endforeach; ?>
    <div class="fill-card fill-note">
      <h2>Good to know</h2>
      <p>All shapes and sizes are a guide. Any of them can be 2–3 layers high, making the cake 4″ or 6″ tall, and we can adjust to what you need.</p>
      <p><b>All of our custom cakes are wet with liqueur.</b> If you don’t want any alcohol, please tell us and it will be left out.</p>
    </div>
  </div>
</section>
<?php order_band(); page_end();
