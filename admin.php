<?php
session_start();
$ADMIN_PASSWORD = 'realtor2025';
$DATA_FILE = __DIR__ . '/leads.json';
$STATS_FILE = __DIR__ . '/stats.json';
$CONFIG_FILE = __DIR__ . '/config.json';

if (!file_exists($DATA_FILE)) file_put_contents($DATA_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if (!file_exists($STATS_FILE)) file_put_contents($STATS_FILE, json_encode(['total_forms' => 0, 'today' => ['date' => date('Y-m-d'), 'forms' => 0, 'views' => 0]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if (!file_exists($CONFIG_FILE)) {
    $defaultConfig = [
        'site' => ['title' => 'Риэлтор-Александр', 'description' => 'Недвижимость в Крыму'],
        'hero' => ['title' => 'Недвижимость Крыма', 'subtitle' => 'Покупка, продажа и аренда'],
        'contact' => ['phone' => '+7 (978) 732-42-32', 'email' => 'crimax@inbox.ru']
    ];
    file_put_contents($CONFIG_FILE, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) { $_SESSION['admin_logged_in'] = true; header('Location: admin.php'); exit; }
    else $error = 'Неверный пароль';
}

$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// Обработка сохранения конфигурации
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $config = json_decode(file_get_contents($CONFIG_FILE), true) ?: [];
    
    // Обновляем все поля конфигурации
    if (isset($_POST['site'])) $config['site'] = $_POST['site'];
    if (isset($_POST['colors'])) $config['colors'] = $_POST['colors'];
    if (isset($_POST['header'])) $config['header'] = $_POST['header'];
    if (isset($_POST['hero'])) $config['hero'] = $_POST['hero'];
    if (isset($_POST['about'])) $config['about'] = $_POST['about'];
    if (isset($_POST['services'])) $config['services'] = $_POST['services'];
    if (isset($_POST['properties'])) $config['properties'] = $_POST['properties'];
    if (isset($_POST['reviews'])) $config['reviews'] = $_POST['reviews'];
    if (isset($_POST['contact'])) $config['contact'] = $_POST['contact'];
    if (isset($_POST['footer'])) $config['footer'] = $_POST['footer'];
    if (isset($_POST['telegram'])) $config['telegram'] = $_POST['telegram'];
    
    file_put_contents($CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success_msg = 'Конфигурация успешно сохранена!';
    header('Location: admin.php?tab=settings&saved=1'); exit;
}

// Обработка добавления объекта
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    $config = json_decode(file_get_contents($CONFIG_FILE), true) ?: [];
    $newProperty = [
        'id' => count($config['properties']['items'] ?? []) + 1,
        'title' => $_POST['title'] ?? 'Новый объект',
        'price' => $_POST['price'] ?? '0 ₽',
        'area' => $_POST['area'] ?? '',
        'rooms' => $_POST['rooms'] ?? '1',
        'img' => $_POST['img'] ?? '',
        'location' => $_POST['location'] ?? '',
        'badge' => $_POST['badge'] ?? '',
        'description' => $_POST['description'] ?? '',
        'gallery' => [],
        'features' => [],
        'quick' => []
    ];
    $config['properties']['items'][] = $newProperty;
    file_put_contents($CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: admin.php?tab=properties&added=1'); exit;
}

// Обработка удаления объекта
if ($isLoggedIn && isset($_GET['delete_property']) && is_numeric($_GET['delete_property'])) {
    $config = json_decode(file_get_contents($CONFIG_FILE), true) ?: [];
    if (isset($config['properties']['items'][$_GET['delete_property']])) {
        array_splice($config['properties']['items'], $_GET['delete_property'], 1);
        file_put_contents($CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: admin.php?tab=properties'); exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Админ-панель | Риэлтор Александр</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f11;--surface:#1a1a1d;--orange:#E65C00;--orange-light:#FF7A00;--text:#F5F5F5;--text-muted:#9CA3AF;--border:#2D2D30;--success:#10B981;--error:#EF4444;--info:#3B82F6;--radius:12px;--shadow:0 8px 32px rgba(0,0,0,0.4)}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}a{text-decoration:none;color:inherit}
.login-container{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:radial-gradient(ellipse at top,rgba(230,92,0,0.1) 0%,transparent 50%)}
.login-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px;width:100%;max-width:400px;box-shadow:var(--shadow)}
.login-logo{text-align:center;margin-bottom:30px}.login-logo h1{font-size:1.5rem;background:linear-gradient(135deg,var(--orange),var(--orange-light));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}.login-logo p{color:var(--text-muted);font-size:0.9rem;margin-top:8px}
.form-group{margin-bottom:20px}.form-label{display:block;margin-bottom:8px;font-size:0.9rem;color:var(--text-muted)}
.form-input{width:100%;padding:14px 16px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:1rem;transition:all 0.25s ease}
.form-input:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(230,92,0,0.15)}
.btn{width:100%;padding:14px 24px;background:var(--orange);color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;transition:all 0.25s ease}
.btn:hover{background:var(--orange-light);transform:translateY(-2px);box-shadow:0 8px 20px rgba(230,92,0,0.35)}
.btn-outline{background:transparent;border:2px solid var(--border);color:var(--text-muted)}.btn-outline:hover{border-color:var(--orange);color:var(--orange);transform:none}
.btn-sm{padding:8px 16px;font-size:0.85rem;width:auto}.btn-danger{background:var(--error)}.btn-danger:hover{background:#DC2626}
.error-msg{color:var(--error);font-size:0.9rem;margin-bottom:16px;padding:12px;background:rgba(239,68,68,0.1);border-radius:8px;border-left:3px solid var(--error)}
.admin-layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}@media(max-width:768px){.admin-layout{grid-template-columns:1fr}}
.sidebar{background:var(--surface);border-right:1px solid var(--border);padding:24px;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-logo{font-size:1.3rem;font-weight:700;margin-bottom:40px;padding-bottom:20px;border-bottom:1px solid var(--border)}.sidebar-logo span{color:var(--orange)}
.nav-menu{list-style:none}.nav-item{margin-bottom:8px}
.nav-link{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:8px;color:var(--text-muted);transition:all 0.25s ease;font-weight:500}
.nav-link:hover,.nav-link.active{background:rgba(230,92,0,0.1);color:var(--orange)}.nav-link.active{border-left:3px solid var(--orange)}
.nav-icon{font-size:1.2rem}.sidebar-footer{margin-top:auto;padding-top:20px;border-top:1px solid var(--border)}
.main-content{padding:32px;overflow-y:auto}.page-header{margin-bottom:32px}.page-title{font-size:1.8rem;font-weight:700;margin-bottom:8px}.page-subtitle{color:var(--text-muted)}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-bottom:32px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;transition:all 0.25s ease}
.stat-card:hover{border-color:var(--orange);transform:translateY(-4px);box-shadow:var(--shadow)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:16px}
.stat-icon.orange{background:rgba(230,92,0,0.15);color:var(--orange)}.stat-icon.green{background:rgba(16,185,129,0.15);color:var(--success)}.stat-icon.blue{background:rgba(59,130,246,0.15);color:var(--info)}.stat-icon.purple{background:rgba(139,92,246,0.15);color:#8B5CF6}
.stat-value{font-size:2rem;font-weight:700;margin-bottom:4px}.stat-label{color:var(--text-muted);font-size:0.9rem}
.table-container{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.table-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}.table-title{font-size:1.1rem;font-weight:600}
table{width:100%;border-collapse:collapse}th,td{padding:16px 24px;text-align:left;border-bottom:1px solid var(--border)}
th{background:rgba(0,0,0,0.2);font-weight:600;font-size:0.85rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px}
tr:last-child td{border-bottom:none}tr:hover{background:rgba(230,92,0,0.03)}
.action-btns{display:flex;gap:8px}.action-btn{padding:6px 12px;border-radius:6px;font-size:0.85rem;cursor:pointer;transition:all 0.25s ease;border:none}
.action-btn.view{background:rgba(59,130,246,0.15);color:var(--info)}.action-btn.view:hover{background:var(--info);color:#fff}
.action-btn.delete{background:rgba(239,68,68,0.15);color:var(--error)}.action-btn.delete:hover{background:var(--error);color:#fff}
.empty-state{padding:60px 20px;text-align:center;color:var(--text-muted)}.empty-state-icon{font-size:3rem;margin-bottom:16px;opacity:0.5}
.modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity 0.3s ease}
.modal.open{opacity:1;pointer-events:auto}.modal-overlay{position:absolute;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(4px)}
.modal-content{position:relative;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:32px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;transform:scale(0.95);transition:transform 0.3s ease}
.modal.open .modal-content{transform:scale(1)}.modal-close{position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:50%;background:var(--bg);border:1px solid var(--border);color:var(--text);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.25s ease}
.modal-close:hover{background:var(--orange);border-color:var(--orange)}
.detail-row{display:flex;padding:12px 0;border-bottom:1px solid var(--border)}.detail-row:last-child{border-bottom:none}
.detail-label{width:120px;color:var(--text-muted);font-size:0.9rem}.detail-value{flex:1;color:var(--text);word-break:break-all}
.mobile-menu-btn{display:none;position:fixed;bottom:20px;right:20px;width:56px;height:56px;border-radius:50%;background:var(--orange);border:none;color:#fff;font-size:1.5rem;cursor:pointer;z-index:99;box-shadow:0 4px 20px rgba(230,92,0,0.4)}
@media(max-width:768px){.sidebar{position:fixed;left:-260px;top:0;bottom:0;z-index:100;transition:left 0.3s ease}.sidebar.mobile-open{left:0}.mobile-menu-btn{display:block}.main-content{padding:20px}table{font-size:0.85rem}th,td{padding:12px 16px}}
.tab-content{display:none}.tab-content.active{display:block}code{background:rgba(230,92,0,0.1);padding:2px 6px;border-radius:4px;font-size:0.9em}
</style>
</head>
<body>
<?php if (!$isLoggedIn): ?>
<div class="login-container"><div class="login-box"><div class="login-logo"><h1>🏠 Админ-панель</h1><p>Риэлтор Александр</p></div>
<?php if ($error): ?><div class="error-msg"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="POST"><div class="form-group"><label class="form-label">Пароль</label><input type="password" name="password" class="form-input" placeholder="Введите пароль" required autofocus></div>
<button type="submit" class="btn">Войти</button></form>
<div style="margin-top:20px;text-align:center"><a href="index.html" class="btn btn-outline btn-sm" style="display:inline-flex;width:auto">← На сайт</a></div></div></div>
<?php else:
$leads = json_decode(file_get_contents($DATA_FILE), true) ?: [];
$stats = json_decode(file_get_contents($STATS_FILE), true) ?: [];
$todayStats = $stats['today'] ?? ['forms'=>0,'views'=>0];
$totalForms = $stats['total_forms'] ?? 0;
$tab = $_GET['tab'] ?? 'dashboard';
?>
<button class="mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('mobile-open')">☰</button>
<div class="admin-layout">
<aside class="sidebar" id="sidebar"><div class="sidebar-logo">🏠 <span>Admin</span></div>
<ul class="nav-menu">
<li class="nav-item"><a href="?tab=dashboard" class="nav-link <?php echo $tab==='dashboard'?'active':'';?>"><span class="nav-icon">📊</span>Дашборд</a></li>
<li class="nav-item"><a href="?tab=leads" class="nav-link <?php echo $tab==='leads'?'active':'';?>"><span class="nav-icon">📋</span>Заявки<?php if(count($leads)>0) echo " <span style='margin-left:auto;background:var(--orange);color:#fff;padding:2px 8px;border-radius:10px;font-size:0.75rem'>".count($leads)."</span>";?></a></li>
<li class="nav-item"><a href="?tab=properties" class="nav-link <?php echo $tab==='properties'?'active':'';?>"><span class="nav-icon">🏠</span>Объекты</a></li>
<li class="nav-item"><a href="?tab=stats" class="nav-link <?php echo $tab==='stats'?'active':'';?>"><span class="nav-icon">📈</span>Статистика</a></li>
<li class="nav-item"><a href="?tab=settings" class="nav-link <?php echo $tab==='settings'?'active':'';?>"><span class="nav-icon">⚙️</span>Настройки сайта</a></li>
</ul>
<div class="sidebar-footer"><a href="?logout=1" class="nav-link"><span class="nav-icon">🚪</span>Выйти</a><a href="index.html" class="nav-link" target="_blank"><span class="nav-icon">🌐</span>На сайт</a></div>
</aside>
<main class="main-content">
<div class="tab-content <?php echo $tab==='dashboard'?'active':'';?>" id="tab-dashboard">
<div class="page-header"><h1 class="page-title">Дашборд</h1><p class="page-subtitle">Обзор активности</p></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon orange">📋</div><div class="stat-value"><?php echo count($leads); ?></div><div class="stat-label">Новых заявок</div></div>
<div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-value"><?php echo $todayStats['forms']; ?></div><div class="stat-label">Заявок сегодня</div></div>
<div class="stat-card"><div class="stat-icon blue">📊</div><div class="stat-value"><?php echo $totalForms; ?></div><div class="stat-label">Всего заявок</div></div>
<div class="stat-card"><div class="stat-icon purple">⭐</div><div class="stat-value">12</div><div class="stat-label">Объектов</div></div>
</div>
<div class="table-container"><div class="table-header"><h3 class="table-title">Последние заявки</h3><a href="?tab=leads" class="btn btn-outline btn-sm">Все →</a></div>
<table><thead><tr><th>Имя</th><th>Телефон</th><th>Дата</th><th>Действия</th></tr></thead><tbody>
<?php $recent = array_slice(array_reverse($leads),0,5); if(empty($recent)):?><tr><td colspan="4" class="empty-state"><div class="empty-state-icon">📭</div>Пока нет заявок</td></tr>
<?php else: foreach($recent as $lead):?><tr><td><?php echo htmlspecialchars($lead['name']);?></td><td><code><?php echo htmlspecialchars($lead['phone']);?></code></td><td><?php echo date('d.m.Y H:i',strtotime($lead['created_at']));?></td><td><button class="action-btn view" onclick="viewLead(<?php echo array_search($lead,$leads);?>)">👁️</button></td></tr><?php endforeach; endif;?>
</tbody></table></div></div>

<div class="tab-content <?php echo $tab==='leads'?'active':'';?>" id="tab-leads">
<div class="page-header"><h1 class="page-title">Заявки</h1><p class="page-subtitle">Управление заявками</p></div>
<div class="table-container"><div class="table-header"><h3 class="table-title">Все заявки (<?php echo count($leads);?>)</h3><?php if(!empty($leads)):?><a href="?clear_all=1" class="btn btn-danger btn-sm" onclick="return confirm('Удалить все?')">Очистить</a><?php endif;?></div>
<table><thead><tr><th>#</th><th>Имя</th><th>Телефон</th><th>Сообщение</th><th>IP</th><th>Дата</th><th>Действия</th></tr></thead><tbody>
<?php if(empty($leads)):?><tr><td colspan="7" class="empty-state"><div class="empty-state-icon">📭</div>Нет заявок</td></tr>
<?php else: foreach(array_reverse($leads) as $idx=>$lead):$ri=(count($leads)-1)-$idx;?>
<tr><td><?php echo $ri+1;?></td><td><?php echo htmlspecialchars($lead['name']);?></td><td><code><?php echo htmlspecialchars($lead['phone']);?></code></td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars(mb_substr($lead['message'],0,50));?></td><td><code><?php echo htmlspecialchars($lead['ip']);?></code></td><td><?php echo date('d.m.Y H:i',strtotime($lead['created_at']));?></td><td><div class="action-btns"><button class="action-btn view" onclick="viewLead(<?php echo $ri;?>)">👁️</button><a href="tel:<?php echo urlencode($lead['phone']);?>" class="action-btn view">📞</a><button class="action-btn delete" onclick="if(confirm('Удалить?'))window.location='?delete=<?php echo $ri;?>'" title="Удалить">🗑️</button></div></td></tr><?php endforeach; endif;?>
</tbody></table></div></div>

<div class="tab-content <?php echo $tab==='stats'?'active':'';?>" id="tab-stats">
<div class="page-header"><h1 class="page-title">Статистика</h1><p class="page-subtitle">Аналитика</p></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon orange">📋</div><div class="stat-value"><?php echo $totalForms;?></div><div class="stat-label">Всего заявок</div></div>
<div class="stat-card"><div class="stat-icon green">📅</div><div class="stat-value"><?php echo $todayStats['forms'];?></div><div class="stat-label">Сегодня</div></div>
<div class="stat-card"><div class="stat-icon blue">👁️</div><div class="stat-value"><?php echo $todayStats['views']??0;?></div><div class="stat-label">Просмотров</div></div>
<div class="stat-card"><div class="stat-icon purple">📊</div><div class="stat-value"><?php echo $todayStats['forms']>0?round(($todayStats['forms']/max($todayStats['views']??1,1))*100,1):0;?>%</div><div class="stat-label">Конверсия</div></div>
</div></div>

<div class="tab-content <?php echo $tab==='properties'?'active':'';?>" id="tab-properties">
<div class="page-header"><h1 class="page-title">Объекты недвижимости</h1><p class="page-subtitle">Управление объектами</p></div>
<?php 
$config = json_decode(file_get_contents($CONFIG_FILE), true) ?: [];
$properties = $config['properties']['items'] ?? [];
?>
<div style="margin-bottom:20px;padding:20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
<h3 style="margin-bottom:16px">➕ Добавить объект</h3>
<form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
<input type="text" name="title" placeholder="Название объекта" class="form-input" required>
<input type="text" name="price" placeholder="Цена (например: 5 000 000 ₽)" class="form-input" required>
<input type="text" name="area" placeholder="Площадь (например: 45 м²)" class="form-input">
<input type="text" name="rooms" placeholder="Комнат" class="form-input" value="1">
<input type="text" name="img" placeholder="Путь к фото (images/...)" class="form-input">
<input type="text" name="location" placeholder="Адрес" class="form-input" style="grid-column:span 2">
<input type="text" name="badge" placeholder="Бейдж (Новая цена, Хит и т.д.)" class="form-input">
<textarea name="description" placeholder="Описание объекта" class="form-input" style="grid-column:span 2;min-height:80px"></textarea>
<button type="submit" name="add_property" class="btn" style="grid-column:span 2">Добавить объект</button>
</form>
</div>

<div class="table-container">
<div class="table-header"><h3 class="table-title">Все объекты (<?php echo count($properties); ?>)</h3></div>
<table><thead><tr><th>#</th><th>Фото</th><th>Название</th><th>Цена</th><th>Площадь</th><th>Адрес</th><th>Действия</th></tr></thead><tbody>
<?php if(empty($properties)):?><tr><td colspan="7" class="empty-state"><div class="empty-state-icon">🏠</div>Нет объектов</td></tr>
<?php else: foreach($properties as $idx=>$prop):?>
<tr>
<td><?php echo $idx+1;?></td>
<td style="width:80px"><?php if(!empty($prop['img'])):?><img src="<?php echo htmlspecialchars($prop['img']);?>" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:4px"><?php else:?><span style="color:var(--text-muted)">Нет фото</span><?php endif;?></td>
<td><?php echo htmlspecialchars($prop['title']);?></td>
<td style="color:var(--orange);font-weight:600"><?php echo htmlspecialchars($prop['price']);?></td>
<td><?php echo htmlspecialchars($prop['area']);?></td>
<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($prop['location']);?></td>
<td><div class="action-btns"><a href="?delete_property=<?php echo $idx;?>" class="action-btn delete" onclick="return confirm('Удалить объект?')">🗑️</a></td></tr>
<?php endforeach; endif;?>
</tbody></table></div></div>

<div class="tab-content <?php echo $tab==='settings'?'active':'';?>" id="tab-settings">
<div class="page-header"><h1 class="page-title">Настройки сайта</h1><p class="page-subtitle">Редактирование контента</p></div>

<?php 
if(isset($_GET['saved'])): ?>
<div style="margin-bottom:20px;padding:16px;background:rgba(16,185,129,0.1);border:1px solid var(--success);border-radius:var(--radius);color:var(--success)">Конфигурация успешно сохранена!</div>
<?php endif;

$config = json_decode(file_get_contents($CONFIG_FILE), true) ?: [];
?>

<form method="POST" style="display:grid;gap:24px">
<!-- Основные настройки -->
<div class="table-container">
<div class="table-header"><h3 class="table-title">📱 Основная информация</h3></div>
<div style="padding:20px;display:grid;gap:16px">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px">
<div><label class="form-label">Заголовок сайта (title)</label><input type="text" name="site[title]" value="<?php echo htmlspecialchars($config['site']['title'] ?? ''); ?>" class="form-input"></div>
<div><label class="form-label">Описание (description)</label><input type="text" name="site[description]" value="<?php echo htmlspecialchars($config['site']['description'] ?? ''); ?>" class="form-input"></div>
</div>
</div>
</div>

<!-- Шапка -->
<div class="table-container">
<div class="table-header"><h3 class="table-title">🎨 Цветовая схема</h3></div>
<div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
<?php foreach(($config['colors'] ?? []) as $key=>$value):?>
<div><label class="form-label"><?php echo ucfirst($key); ?></label><input type="color" name="colors[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>" style="width:100%;height:40px;border:none;border-radius:8px;cursor:pointer"></div>
<?php endforeach;?>
</div>
</div>

<!-- Hero секция -->
<div class="table-container">
<div class="table-header"><h3 class="table-title">🏠 Главный экран (Hero)</h3></div>
<div style="padding:20px;display:grid;gap:16px">
<label class="form-label">Заголовок</label><input type="text" name="hero[title]" value="<?php echo htmlspecialchars($config['hero']['title'] ?? ''); ?>" class="form-input">
<label class="form-label">Подзаголовок</label><textarea name="hero[subtitle]" class="form-input" style="min-height:60px"><?php echo htmlspecialchars($config['hero']['subtitle'] ?? ''); ?></textarea>
<label class="form-label">Текст кнопки (заявка)</label><input type="text" name="hero[btn_primary]" value="<?php echo htmlspecialchars($config['hero']['btn_primary'] ?? 'Оставить заявку'); ?>" class="form-input">
<label class="form-label">Текст кнопки (объекты)</label><input type="text" name="hero[btn_secondary]" value="<?php echo htmlspecialchars($config['hero']['btn_secondary'] ?? 'Смотреть объекты'); ?>" class="form-input">
</div>
</div>

<!-- Контакты -->
<div class="table-container">
<div class="table-header"><h3 class="table-title">📞 Контактная информация</h3></div>
<div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px">
<div><label class="form-label">Телефон</label><input type="text" name="contact[phone]" value="<?php echo htmlspecialchars($config['contact']['phone'] ?? ''); ?>" class="form-input"></div>
<div><label class="form-label">Email</label><input type="email" name="contact[email]" value="<?php echo htmlspecialchars($config['contact']['email'] ?? ''); ?>" class="form-input"></div>
<div style="grid-column:span 2"><label class="form-label">Адрес</label><input type="text" name="contact[address]" value="<?php echo htmlspecialchars($config['contact']['address'] ?? ''); ?>" class="form-input"></div>
<div style="grid-column:span 2"><label class="form-label">Описание</label><textarea name="contact[description]" class="form-input" style="min-height:60px"><?php echo htmlspecialchars($config['contact']['description'] ?? ''); ?></textarea></div>
</div>
</div>

<!-- Telegram -->
<div class="table-container">
<div class="table-header"><h3 class="table-title">✈️ Telegram бот</h3></div>
<div style="padding:20px;display:grid;gap:16px">
<div><label class="form-label">Bot Token</label><input type="text" name="telegram[bot_token]" value="<?php echo htmlspecialchars($config['telegram']['bot_token'] ?? ''); ?>" class="form-input" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"></div>
<div><label class="form-label">Chat IDs (через запятую)</label><input type="text" name="telegram[chat_ids]" value="<?php echo htmlspecialchars(implode(',', $config['telegram']['chat_ids'] ?? [])); ?>" class="form-input" placeholder="123456789,987654321"></div>
</div>
</div>

<button type="submit" name="save_config" class="btn glow">💾 Сохранить все изменения</button>
</form>

<div style="margin-top:20px;padding:20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">
<h3 style="margin-bottom:12px">ℹ️ Как использовать</h3>
<ul style="color:var(--text-muted);line-height:1.8;padding-left:20px">
<li>Изменения вступают в силу сразу после сохранения</li>
<li>Для применения изменений на сайте может потребоваться обновить страницу (F5)</li>
<li>Все данные хранятся в файле config.json</li>
<li>Чтобы сбросить настройки - удалите файл config.json</li>
</ul></div></div>
</main></div>

<div class="modal" id="leadModal"><div class="modal-overlay" onclick="closeModal()"></div><div class="modal-content"><button class="modal-close" onclick="closeModal()">✕</button><h2 style="margin-bottom:20px">Детали заявки</h2><div id="leadDetails"></div></div></div>
<script>
const leads=<?php echo json_encode($leads,JSON_UNESCAPED_UNICODE);?>;
function viewLead(i){const l=leads[i];if(!l)return;document.getElementById('leadDetails').innerHTML='<div class="detail-row"><span class="detail-label">Имя:</span><span class="detail-value">'+esc(l.name)+'</span></div><div class="detail-row"><span class="detail-label">Телефон:</span><span class="detail-value"><code>'+esc(l.phone)+'</code></span></div><div class="detail-row"><span class="detail-label">Сообщение:</span><span class="detail-value">'+esc(l.message)+'</span></div><div class="detail-row"><span class="detail-label">IP:</span><span class="detail-value"><code>'+esc(l.ip)+'</code></span></div><div class="detail-row"><span class="detail-label">Дата:</span><span class="detail-value">'+new Date(l.created_at).toLocaleString('ru-RU')+'</span></div>';document.getElementById('leadModal').classList.add('open')}
function closeModal(){document.getElementById('leadModal').classList.remove('open')}
function esc(t){const d=document.createElement('div');d.textContent=t;return d.innerHTML}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
</script>
<?php endif; ?>
</body>
</html>
