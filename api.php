<?php
/**
 * API Endpoints for Restaurant POS
 * Handles all AJAX requests
 */

header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {

        // ─── CATEGORIES ─────────────────────────────────────────
        case 'get_categories':
            $stmt = $db->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'get_all_categories':
            $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'add_category':
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '🍽️');
            $sort = intval($_POST['sort_order'] ?? 0);
            if (empty($name)) throw new Exception('Category name is required');
            $stmt = $db->prepare("INSERT INTO categories (name, icon, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $icon, $sort]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;

        case 'update_category':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $icon = trim($_POST['icon'] ?? '🍽️');
            $sort = intval($_POST['sort_order'] ?? 0);
            $active = intval($_POST['active'] ?? 1);
            $stmt = $db->prepare("UPDATE categories SET name=?, icon=?, sort_order=?, active=? WHERE id=?");
            $stmt->execute([$name, $icon, $sort, $active, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_category':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ─── ITEMS ──────────────────────────────────────────────
        case 'get_items':
            $catId = $_GET['category_id'] ?? null;
            $search = $_GET['search'] ?? '';

            $sql = "SELECT i.*, c.name as category_name FROM items i 
                    LEFT JOIN categories c ON i.category_id = c.id 
                    WHERE i.active = 1";
            $params = [];

            if ($catId && $catId !== 'all') {
                $sql .= " AND i.category_id = ?";
                $params[] = intval($catId);
            }
            if (!empty($search)) {
                $sql .= " AND i.name LIKE ?";
                $params[] = "%$search%";
            }
            $sql .= " ORDER BY i.name ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'get_all_items':
            $stmt = $db->query("SELECT i.*, c.name as category_name FROM items i 
                               LEFT JOIN categories c ON i.category_id = c.id 
                               ORDER BY c.sort_order ASC, i.name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'add_item':
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $catId = intval($_POST['category_id'] ?? 0);
            $emoji = trim($_POST['emoji'] ?? '🍴');
            $desc = trim($_POST['description'] ?? '');
            if (empty($name)) throw new Exception('Item name is required');
            if ($price <= 0) throw new Exception('Price must be greater than 0');
            $stmt = $db->prepare("INSERT INTO items (category_id, name, price, emoji, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$catId, $name, $price, $emoji, $desc]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;

        case 'update_item':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $catId = intval($_POST['category_id'] ?? 0);
            $emoji = trim($_POST['emoji'] ?? '🍴');
            $desc = trim($_POST['description'] ?? '');
            $active = intval($_POST['active'] ?? 1);
            $stmt = $db->prepare("UPDATE items SET category_id=?, name=?, price=?, emoji=?, description=?, active=? WHERE id=?");
            $stmt->execute([$catId, $name, $price, $emoji, $desc, $active, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_item':
            $id = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ─── BILLING ────────────────────────────────────────────
        case 'create_bill':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || empty($data['items'])) throw new Exception('No items in the bill');

            $billNumber = 'BILL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $subtotal = floatval($data['subtotal'] ?? 0);
            $taxPercent = floatval($data['tax_percent'] ?? 5);
            $taxAmount = floatval($data['tax_amount'] ?? 0);
            $discountPercent = floatval($data['discount_percent'] ?? 0);
            $discountAmount = floatval($data['discount_amount'] ?? 0);
            $total = floatval($data['total'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'cash';

            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO bills (bill_number, subtotal, tax_percent, tax_amount, discount_percent, discount_amount, total, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$billNumber, $subtotal, $taxPercent, $taxAmount, $discountPercent, $discountAmount, $total, $paymentMethod]);
            $billId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO bill_items (bill_id, item_id, item_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($data['items'] as $item) {
                $stmt->execute([
                    $billId,
                    intval($item['id']),
                    $item['name'],
                    intval($item['quantity']),
                    floatval($item['price']),
                    floatval($item['price']) * intval($item['quantity'])
                ]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'bill_id' => $billId, 'bill_number' => $billNumber]);
            break;

        case 'get_bills':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $date = $_GET['date'] ?? '';

            $sql = "SELECT * FROM bills WHERE 1=1";
            $countSql = "SELECT COUNT(*) FROM bills WHERE 1=1";
            $params = [];

            if (!empty($date)) {
                $sql .= " AND DATE(created_at) = ?";
                $countSql .= " AND DATE(created_at) = ?";
                $params[] = $date;
            }

            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();

            $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(),
                'total' => $totalCount,
                'pages' => ceil($totalCount / $limit),
                'page' => $page
            ]);
            break;

        case 'get_bill_detail':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM bills WHERE id = ?");
            $stmt->execute([$id]);
            $bill = $stmt->fetch();
            if (!$bill) throw new Exception('Bill not found');

            $stmt = $db->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
            $stmt->execute([$id]);
            $bill['items'] = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $bill]);
            break;

        // ─── DASHBOARD ──────────────────────────────────────────
        case 'get_dashboard':
            $today = date('Y-m-d');

            $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as total FROM bills WHERE DATE(created_at) = ? AND status = 'completed'");
            $stmt->execute([$today]);
            $todayStats = $stmt->fetch();

            $stmt = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as total FROM bills WHERE status = 'completed'");
            $allStats = $stmt->fetch();

            $stmt = $db->query("SELECT COUNT(*) FROM items WHERE active = 1");
            $itemCount = $stmt->fetchColumn();

            $stmt = $db->query("SELECT COUNT(*) FROM categories WHERE active = 1");
            $catCount = $stmt->fetchColumn();

            // Recent bills
            $stmt = $db->query("SELECT * FROM bills ORDER BY created_at DESC LIMIT 5");
            $recentBills = $stmt->fetchAll();

            // Top items
            $stmt = $db->query("SELECT item_name, SUM(quantity) as total_qty, SUM(total) as total_revenue 
                               FROM bill_items GROUP BY item_name ORDER BY total_qty DESC LIMIT 5");
            $topItems = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'today_bills' => $todayStats['count'],
                'today_revenue' => $todayStats['total'],
                'total_bills' => $allStats['count'],
                'total_revenue' => $allStats['total'],
                'item_count' => $itemCount,
                'category_count' => $catCount,
                'recent_bills' => $recentBills,
                'top_items' => $topItems
            ]);
            break;

        default:
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
