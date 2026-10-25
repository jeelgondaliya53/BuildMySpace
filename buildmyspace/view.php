<?php
require __DIR__ . '/config.php';
$st = db()->prepare('SELECT * FROM estimates WHERE id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$row = $st->fetch();
$r = $row ? json_decode($row['result'], true) : null;
$money = fn($n) => CURRENCY . number_format((float)$n, 0, '.', ',');
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estimate #<?= $row ? (int)$row['id'] : '' ?> · BuildMySpace</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="doc">
<main class="sheet">
<?php if (!$r): ?>
  <h1>Estimate not found</h1>
  <p>Check the link, or <a href="index.php">start a new estimate</a>.</p>
<?php else: $i = $r['inputs']; ?>
  <p class="brand">BuildMySpace</p>
  <h1>Estimate for <?= $h($row['name']) ?></h1>
  <p class="muted">Saved <?= $h($row['created_at']) ?> · Estimate #<?= (int)$row['id'] ?></p>
  <p class="big"><?= $money($r['total']) ?></p>
  <p><?= $h($r['quality']) ?> finish · <?= number_format($r['built_up']) ?> sq ft built-up · <?= (int)$i['floors'] ?> floor(s) · <?= (int)$i['beds'] ?> bed · <?= (int)$i['baths'] ?> bath</p>
  <table>
    <tr><th>Item</th><th class="num">Cost</th></tr>
    <?php foreach ($r['breakdown'] as $b): ?>
      <tr><td><?= $h($b['label']) ?></td><td class="num"><?= $money($b['amount']) ?></td></tr>
    <?php endforeach; foreach ($r['extras'] as $b): ?>
      <tr><td><?= $h($b['label']) ?> (add-on)</td><td class="num"><?= $money($b['amount']) ?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td>Total</td><td class="num"><?= $money($r['total']) ?></td></tr>
  </table>
  <p class="muted">This is a planning estimate. Get quotes from local contractors before you commit.</p>
  <p class="actions"><button onclick="window.print()">Print this estimate</button> <a href="index.php">New estimate</a></p>
<?php endif; ?>
</main>
</body>
</html>
