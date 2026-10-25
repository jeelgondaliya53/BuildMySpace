<?php
require __DIR__ . '/config.php';
$qualities = db()->query('SELECT * FROM quality_rates ORDER BY rate_per_sqft')->fetchAll();
$extras = db()->query('SELECT * FROM extras ORDER BY label')->fetchAll();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BuildMySpace · House design & cost estimator</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Public+Sans:wght@400;600&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <span class="brand">BuildMySpace</span>
  <p>Shape your house, see the cost update as you go.</p>
</header>

<main class="app">
  <form id="est" class="panel" autocomplete="off">
    <fieldset>
      <legend>Plot</legend>
      <label>Plot area (sq ft) <input type="number" name="plot" value="1200" min="300" max="20000" step="50"></label>
      <label>Ground coverage: <output id="covOut">60</output>%
        <input type="range" name="coverage" value="60" min="30" max="90" step="5"></label>
      <label>Floors
        <select name="floors"><option>1</option><option>2</option><option>3</option><option>4</option></select></label>
    </fieldset>

    <fieldset>
      <legend>Rooms</legend>
      <label>Bedrooms <input type="number" name="beds" value="2" min="1" max="8"></label>
      <label>Bathrooms <input type="number" name="baths" value="2" min="1" max="8"></label>
      <label class="check"><input type="checkbox" name="dining" checked> Separate dining room</label>
      <label class="check"><input type="checkbox" name="parking" checked> Covered parking</label>
    </fieldset>

    <fieldset>
      <legend>Finish quality</legend>
      <?php foreach ($qualities as $k => $q): ?>
        <label class="check radio">
          <input type="radio" name="quality" value="<?= $h($q['code']) ?>" <?= $q['code'] === 'standard' ? 'checked' : '' ?>>
          <span><strong><?= $h($q['label']) ?></strong> · <?= CURRENCY . number_format($q['rate_per_sqft']) ?>/sq ft<br><small><?= $h($q['note']) ?></small></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <fieldset>
      <legend>Add-ons</legend>
      <?php foreach ($extras as $e): ?>
        <label class="check">
          <input type="checkbox" name="extras" value="<?= $h($e['code']) ?>">
          <?= $h($e['label']) ?>
          <small>(<?= CURRENCY . number_format($e['cost']) ?><?= $e['type'] === 'per_sqft' ? '/sq ft' : '' ?>)</small>
        </label>
      <?php endforeach; ?>
    </fieldset>
  </form>

  <section class="out">
    <div class="plan-wrap">
      <h2>Floor plan sketch</h2>
      <div id="tabs" class="tabs"></div>
      <div id="plan" class="plan"></div>
      <p id="planCap" class="muted"></p>
      <p class="muted">A rough sketch to show how the rooms could fit. A final layout needs an architect.</p>
    </div>

    <div class="cost">
      <p class="muted">Estimated cost</p>
      <p id="total" class="big">–</p>
      <p id="meta" class="muted"></p>
      <div id="rows" class="rows"></div>

      <form id="saveForm" class="save">
        <h3>Save this estimate</h3>
        <input name="name" placeholder="Your name" required maxlength="80">
        <input name="email" type="email" placeholder="Email address" required>
        <button type="submit">Save estimate</button>
        <p id="msg" role="status"></p>
      </form>
    </div>
  </section>
</main>

<script>window.CURRENCY = <?= json_encode(CURRENCY) ?>;</script>
<script src="assets/app.js"></script>
</body>
</html>
