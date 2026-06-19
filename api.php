<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function isAdmin(): bool {
    if (empty($_SESSION['admin_logged_in'])) return false;
    if ((time() - ($_SESSION['admin_login_time'] ?? 0)) > 43200) {
        session_unset(); return false;
    }
    return true;
}
function isPOS(): bool {
    if (empty($_SESSION['pos_logged_in'])) return false;
    if ((time() - ($_SESSION['pos_login_time'] ?? 0)) > 43200) {
        unset($_SESSION['pos_logged_in'],$_SESSION['pos_user_id'],$_SESSION['pos_user_name'],$_SESSION['pos_login_time']);
        return false;
    }
    return true;
}
function requireAdmin(): void {
    if (!isAdmin()) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Admin login required','auth_required'=>'admin']); exit; }
}
function requirePOS(): void {
    if (!isPOS()) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Cashier login required','auth_required'=>'pos']); exit; }
}
function requireAny(): void {
    if (!isAdmin() && !isPOS()) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Login required','auth_required'=>'any']); exit; }
}

try {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_date DATE NOT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default admin
    if ((int)$db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn() === 0) {
        $db->prepare("INSERT INTO admin_users (username,password_hash) VALUES (?,?)")
           ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    switch ($action) {

        // ── AUTH ─────────────────────────────────────────────────────
        case 'admin_login':
            $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
            if (!$u || !$p) throw new Exception('Credentials required');
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username=? LIMIT 1");
            $stmt->execute([$u]); $user = $stmt->fetch();
            if (!$user || !password_verify($p, $user['password_hash'])) throw new Exception('Invalid credentials');
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            $_SESSION['admin_username'] = $user['username'];
            echo json_encode(['success'=>true]);
            break;

        case 'admin_logout':
            session_unset(); session_destroy();
            echo json_encode(['success'=>true]);
            break;

        case 'pos_login':
            $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
            if (!$u || !$p) throw new Exception('Credentials required');
            $stmt = $db->prepare("SELECT * FROM pos_users WHERE username=? AND active=1 LIMIT 1");
            $stmt->execute([$u]); $user = $stmt->fetch();
            if (!$user || !password_verify($p, $user['password_hash'])) throw new Exception('Invalid credentials');
            $_SESSION['pos_logged_in'] = true;
            $_SESSION['pos_login_time'] = time();
            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_user_name'] = $user['name'];
            echo json_encode(['success'=>true,'name'=>$user['name']]);
            break;

        case 'pos_logout':
            unset($_SESSION['pos_logged_in'],$_SESSION['pos_user_id'],$_SESSION['pos_user_name'],$_SESSION['pos_login_time']);
            echo json_encode(['success'=>true]);
            break;

        case 'check_admin_session':
            echo json_encode(['success'=>true,'logged_in'=>isAdmin(),'username'=>$_SESSION['admin_username']??null]);
            break;

        case 'check_pos_session':
            echo json_encode(['success'=>true,'logged_in'=>isPOS(),'name'=>$_SESSION['pos_user_name']??null]);
            break;

        // ── POS USER MANAGEMENT ──────────────────────────────────────
        case 'get_pos_users':
            requireAdmin();
            $stmt = $db->query("SELECT id,name,username,active,created_at FROM pos_users ORDER BY name ASC");
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            break;

        case 'add_pos_user':
            requireAdmin();
            $name = trim($_POST['name']??''); $uname = trim($_POST['username']??'');
            $pass = $_POST['password']??''; $active = intval($_POST['active']??1);
            if (!$name||!$uname||!$pass) throw new Exception('Name, username and password required');
            $db->prepare("INSERT INTO pos_users (name,username,password_hash,active) VALUES (?,?,?,?)")
               ->execute([$name,$uname,password_hash($pass,PASSWORD_DEFAULT),$active]);
            echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            break;

        case 'update_pos_user':
            requireAdmin();
            $id = intval($_POST['id']??0); $name = trim($_POST['name']??'');
            $uname = trim($_POST['username']??''); $pass = $_POST['password']??'';
            $active = intval($_POST['active']??1);
            if (!$id||!$name||!$uname) throw new Exception('Invalid data');
            if (!empty($pass)) {
                $db->prepare("UPDATE pos_users SET name=?,username=?,password_hash=?,active=? WHERE id=?")
                   ->execute([$name,$uname,password_hash($pass,PASSWORD_DEFAULT),$active,$id]);
            } else {
                $db->prepare("UPDATE pos_users SET name=?,username=?,active=? WHERE id=?")
                   ->execute([$name,$uname,$active,$id]);
            }
            echo json_encode(['success'=>true]);
            break;

        case 'delete_pos_user':
            requireAdmin();
            $id = intval($_POST['id']??0);
            $db->prepare("DELETE FROM pos_users WHERE id=?")->execute([$id]);
            echo json_encode(['success'=>true]);
            break;

        case 'update_admin_credentials':
            requireAdmin();
            $currentPass = $_POST['current_password'] ?? '';
            $newUsername  = trim($_POST['new_username'] ?? '');
            $newPassword  = $_POST['new_password'] ?? '';
            $confirmPass  = $_POST['confirm_password'] ?? '';

            if (!$currentPass) throw new Exception('Current password is required');
            if (!$newUsername) throw new Exception('New username is required');
            if (!empty($newPassword) && $newPassword !== $confirmPass)
                throw new Exception('New passwords do not match');

            // Verify current password
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$_SESSION['admin_username']]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($currentPass, $user['password_hash']))
                throw new Exception('Current password is incorrect');

            // Check username uniqueness if changed
            if ($newUsername !== $user['username']) {
                $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
                $stmt->execute([$newUsername, $user['id']]);
                if ($stmt->fetch()) throw new Exception('Username already taken');
            }

            if (!empty($newPassword)) {
                $db->prepare("UPDATE admin_users SET username=?,password_hash=? WHERE id=?")
                   ->execute([$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            } else {
                $db->prepare("UPDATE admin_users SET username=? WHERE id=?")
                   ->execute([$newUsername, $user['id']]);
            }
            $_SESSION['admin_username'] = $newUsername;
            echo json_encode(['success'=>true]);
            break;

        // ── ANALYTICS & EXPENSES ─────────────────────────────────────
        case 'get_daily_stats':
            requireAdmin();
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM bills WHERE DATE(created_at)=? AND status='completed'");
            $stmt->execute([$date]); $income = $stmt->fetchColumn();
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date=?");
            $stmt->execute([$date]); $expenses_total = $stmt->fetchColumn();
            $stmt = $db->prepare("SELECT * FROM expenses WHERE expense_date=? ORDER BY id DESC");
            $stmt->execute([$date]);
            echo json_encode(['success'=>true,'income'=>$income,'total_expenses'=>$expenses_total,
                'net_profit'=>$income-$expenses_total,'expenses'=>$stmt->fetchAll()]);
            break;

        case 'add_expense':
            requireAdmin();
            $date = $_POST['date']??date('Y-m-d');
            $desc = trim($_POST['description']??''); $amount = floatval($_POST['amount']??0);
            if (!$desc||$amount<=0) throw new Exception('Invalid expense details');
            $db->prepare("INSERT INTO expenses (expense_date,description,amount) VALUES (?,?,?)")->execute([$date,$desc,$amount]);
            echo json_encode(['success'=>true]);
            break;

        case 'delete_expense':
            requireAdmin();
            $db->prepare("DELETE FROM expenses WHERE id=?")->execute([intval($_POST['id']??0)]);
            echo json_encode(['success'=>true]);
            break;

        // ── CATEGORIES ───────────────────────────────────────────────
        case 'get_categories':
            $stmt = $db->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order ASC,name ASC");
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            break;

        case 'get_all_categories':
            requireAdmin();
            $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC,name ASC");
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            break;

        case 'add_category':
            requireAdmin();
            $name = trim($_POST['name']??''); $icon = trim($_POST['icon']??'🍽️'); $sort = intval($_POST['sort_order']??0);
            if (!$name) throw new Exception('Category name required');
            $db->prepare("INSERT INTO categories (name,icon,sort_order) VALUES (?,?,?)")->execute([$name,$icon,$sort]);
            echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            break;

        case 'update_category':
            requireAdmin();
            $id=intval($_POST['id']??0); $name=trim($_POST['name']??''); $icon=trim($_POST['icon']??'🍽️');
            $sort=intval($_POST['sort_order']??0); $active=intval($_POST['active']??1);
            $db->prepare("UPDATE categories SET name=?,icon=?,sort_order=?,active=? WHERE id=?")->execute([$name,$icon,$sort,$active,$id]);
            echo json_encode(['success'=>true]);
            break;

        case 'delete_category':
            requireAdmin();
            $db->prepare("DELETE FROM categories WHERE id=?")->execute([intval($_POST['id']??0)]);
            echo json_encode(['success'=>true]);
            break;

        // ── ITEMS ────────────────────────────────────────────────────
        case 'get_items':
            $catId=$_GET['category_id']??null; $search=$_GET['search']??'';
            $sql="SELECT i.*,c.name as category_name FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.active=1";
            $params=[];
            if ($catId&&$catId!=='all') { $sql.=" AND i.category_id=?"; $params[]=intval($catId); }
            if (!empty($search)) { $sql.=" AND i.name LIKE ?"; $params[]="%$search%"; }
            $sql.=" ORDER BY i.name ASC";
            $stmt=$db->prepare($sql); $stmt->execute($params);
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            break;

        case 'get_all_items':
            requireAdmin();
            $stmt=$db->query("SELECT i.*,c.name as category_name FROM items i LEFT JOIN categories c ON i.category_id=c.id ORDER BY c.sort_order ASC,i.name ASC");
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
            break;

        case 'add_item':
            requireAdmin();
            $name=trim($_POST['name']??''); $price=floatval($_POST['price']??0);
            $catId=intval($_POST['category_id']??0); $emoji=trim($_POST['emoji']??'🍴'); $desc=trim($_POST['description']??'');
            if (!$name) throw new Exception('Item name required');
            if ($price<=0) throw new Exception('Price must be > 0');
            $db->prepare("INSERT INTO items (category_id,name,price,emoji,description) VALUES (?,?,?,?,?)")->execute([$catId,$name,$price,$emoji,$desc]);
            echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
            break;

        case 'update_item':
            requireAdmin();
            $id=intval($_POST['id']??0); $name=trim($_POST['name']??''); $price=floatval($_POST['price']??0);
            $catId=intval($_POST['category_id']??0); $emoji=trim($_POST['emoji']??'🍴');
            $desc=trim($_POST['description']??''); $active=intval($_POST['active']??1);
            $db->prepare("UPDATE items SET category_id=?,name=?,price=?,emoji=?,description=?,active=? WHERE id=?")->execute([$catId,$name,$price,$emoji,$desc,$active,$id]);
            echo json_encode(['success'=>true]);
            break;

        case 'delete_item':
            requireAdmin();
            $db->prepare("DELETE FROM items WHERE id=?")->execute([intval($_POST['id']??0)]);
            echo json_encode(['success'=>true]);
            break;

        // ── BILLING ──────────────────────────────────────────────────
        case 'create_bill':
            requirePOS();
            $data=json_decode(file_get_contents('php://input'),true);
            if (!$data||empty($data['items'])) throw new Exception('No items in bill');
            $billNumber='BILL-'.date('Ymd').'-'.str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT);
            $subtotal=floatval($data['subtotal']??0); $taxPct=floatval($data['tax_percent']??0);
            $taxAmt=floatval($data['tax_amount']??0); $discPct=floatval($data['discount_percent']??0);
            $discAmt=floatval($data['discount_amount']??0); $total=floatval($data['total']??0);
            $method=$data['payment_method']??'cash';
            $db->beginTransaction();
            $stmt=$db->prepare("INSERT INTO bills (bill_number,subtotal,tax_percent,tax_amount,discount_percent,discount_amount,total,payment_method) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$billNumber,$subtotal,$taxPct,$taxAmt,$discPct,$discAmt,$total,$method]);
            $billId=$db->lastInsertId();
            $stmt=$db->prepare("INSERT INTO bill_items (bill_id,item_id,item_name,quantity,price,total) VALUES (?,?,?,?,?,?)");
            foreach ($data['items'] as $item) {
                $stmt->execute([$billId,intval($item['id']),$item['name'],intval($item['quantity']),floatval($item['price']),floatval($item['price'])*intval($item['quantity'])]);
            }
            $db->commit();
            echo json_encode(['success'=>true,'bill_id'=>$billId,'bill_number'=>$billNumber]);
            break;

        case 'get_bills':
            requireAny();
            $page=max(1,intval($_GET['page']??1)); $limit=20; $offset=($page-1)*$limit;
            $date=$_GET['date']??'';
            $sql="SELECT * FROM bills WHERE 1=1"; $countSql="SELECT COUNT(*) FROM bills WHERE 1=1"; $params=[];
            if (!empty($date)) { $sql.=" AND DATE(created_at)=?"; $countSql.=" AND DATE(created_at)=?"; $params[]=$date; }
            $total=(int)$db->prepare($countSql)->execute($params) ? $db->prepare($countSql) : null;
            $cStmt=$db->prepare($countSql); $cStmt->execute($params); $totalCount=$cStmt->fetchColumn();
            $sql.=" ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt=$db->prepare($sql); $stmt->execute($params);
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(),'total'=>$totalCount,'pages'=>ceil($totalCount/$limit),'page'=>$page]);
            break;

        case 'get_bill_detail':
            requireAny();
            $id=intval($_GET['id']??0);
            $stmt=$db->prepare("SELECT * FROM bills WHERE id=?"); $stmt->execute([$id]); $bill=$stmt->fetch();
            if (!$bill) throw new Exception('Bill not found');
            $stmt=$db->prepare("SELECT * FROM bill_items WHERE bill_id=?"); $stmt->execute([$id]);
            $bill['items']=$stmt->fetchAll();
            echo json_encode(['success'=>true,'data'=>$bill]);
            break;

        case 'delete_bill':
            requireAdmin();
            $id=intval($_POST['id']??0);
            if ($id<=0) throw new Exception('Invalid bill ID');
            $db->prepare("DELETE FROM bills WHERE id=?")->execute([$id]);
            echo json_encode(['success'=>true]);
            break;

        // ── DASHBOARD ────────────────────────────────────────────────
        case 'get_dashboard':
            requireAdmin();
            $today=date('Y-m-d');
            $stmt=$db->prepare("SELECT COUNT(*) as count,COALESCE(SUM(total),0) as total FROM bills WHERE DATE(created_at)=? AND status='completed'");
            $stmt->execute([$today]); $todayStats=$stmt->fetch();
            $allStats=$db->query("SELECT COUNT(*) as count,COALESCE(SUM(total),0) as total FROM bills WHERE status='completed'")->fetch();
            $itemCount=$db->query("SELECT COUNT(*) FROM items WHERE active=1")->fetchColumn();
            $recentBills=$db->query("SELECT * FROM bills ORDER BY created_at DESC LIMIT 5")->fetchAll();
            // All top items — no LIMIT (paginated client-side)
            $topItems=$db->query("SELECT item_name,SUM(quantity) as total_qty,SUM(total) as total_revenue FROM bill_items GROUP BY item_name ORDER BY total_qty DESC")->fetchAll();
            echo json_encode(['success'=>true,'today_bills'=>$todayStats['count'],'today_revenue'=>$todayStats['total'],
                'total_bills'=>$allStats['count'],'total_revenue'=>$allStats['total'],
                'item_count'=>$itemCount,'recent_bills'=>$recentBills,'top_items'=>$topItems]);
            break;

        default:
            throw new Exception('Invalid action: '.$action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
