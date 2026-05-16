<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("غير مصرح لك بالدخول إلى صفحة المسؤول");
}

$admin_id = (int) $_SESSION['user_id'];

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function decodePayload($payload) {
    $data = json_decode($payload ?? '', true);
    return is_array($data) ? $data : [];
}

function approveAddRequest($conn, $request, $admin_id) {
    $payload = decodePayload($request['payload']);

    $requiredFields = ['title', 'description', 'content', 'weeks', 'time', 'total_chapters', 'steps', 'course_image'];

    foreach ($requiredFields as $field) {
        if (!isset($payload[$field]) || $payload[$field] === '') {
            throw new Exception("بيانات طلب الإضافة غير مكتملة");
        }
    }

    $title = $payload['title'];
    $description = $payload['description'];
    $content = $payload['content'];
    $weeks = (int)$payload['weeks'];
    $time = (int)$payload['time'];
    $total_chapters = (int)$payload['total_chapters'];
    $steps = $payload['steps'];
    $course_image = $payload['course_image'];
    $teacher_id = (int)$request['teacher_id'];

    if ($total_chapters < 1 || $total_chapters > 7) {
        throw new Exception("عدد الوحدات المهارية يجب أن يكون بين 1 و 7");
    }

    $stmt = $conn->prepare("
        INSERT INTO course 
        (title, description, time, weeks, content, steps, teacher_id, course_image, total_chapters)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssiissisi",
        $title,
        $description,
        $time,
        $weeks,
        $content,
        $steps,
        $teacher_id,
        $course_image,
        $total_chapters
    );

    if (!$stmt->execute()) {
        throw new Exception("فشل اعتماد إضافة المهارة");
    }

    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE course_request
        SET status = 'approved',
            reviewed_by_admin_id = ?,
            reviewed_at = NOW()
        WHERE request_id = ?
    ");

    $request_id = (int)$request['request_id'];
    $stmt->bind_param("ii", $admin_id, $request_id);

    if (!$stmt->execute()) {
        throw new Exception("فشل تحديث حالة الطلب");
    }

    $stmt->close();
}

function approveUpdateRequest($conn, $request, $admin_id) {
    $payload = decodePayload($request['payload']);
    $course_code = (int)$request['course_code'];

    if ($course_code <= 0) {
        throw new Exception("رقم المهارة غير صحيح");
    }

    $title = $payload['title'] ?? '';
    $description = $payload['description'] ?? '';
    $content = $payload['content'] ?? '';
    $weeks = (int)($payload['weeks'] ?? 0);
    $time = (int)($payload['time'] ?? 0);
    $total_chapters = (int)($payload['total_chapters'] ?? 0);
    $steps = $payload['steps'] ?? '';
    $course_image = $payload['course_image'] ?? '';

    if (
        $title === '' ||
        $description === '' ||
        $content === '' ||
        $weeks <= 0 ||
        $time <= 0 ||
        $total_chapters < 1 ||
        $total_chapters > 7 ||
        $steps === ''
    ) {
        throw new Exception("بيانات طلب التعديل غير مكتملة");
    }

    if ($course_image !== '') {
        $stmt = $conn->prepare("
            UPDATE course
            SET title = ?,
                description = ?,
                time = ?,
                weeks = ?,
                content = ?,
                steps = ?,
                course_image = ?,
                total_chapters = ?
            WHERE course_code = ?
        ");

        $stmt->bind_param(
            "ssiisssii",
            $title,
            $description,
            $time,
            $weeks,
            $content,
            $steps,
            $course_image,
            $total_chapters,
            $course_code
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE course
            SET title = ?,
                description = ?,
                time = ?,
                weeks = ?,
                content = ?,
                steps = ?,
                total_chapters = ?
            WHERE course_code = ?
        ");

        $stmt->bind_param(
            "ssiissii",
            $title,
            $description,
            $time,
            $weeks,
            $content,
            $steps,
            $total_chapters,
            $course_code
        );
    }

    if (!$stmt->execute()) {
        throw new Exception("فشل تعديل المهارة");
    }

    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE course_request
        SET status = 'approved',
            reviewed_by_admin_id = ?,
            reviewed_at = NOW()
        WHERE request_id = ?
    ");

    $request_id = (int)$request['request_id'];
    $stmt->bind_param("ii", $admin_id, $request_id);

    if (!$stmt->execute()) {
        throw new Exception("فشل تحديث حالة الطلب");
    }

    $stmt->close();
}

function approveDeleteRequest($conn, $request, $admin_id) {
    $course_code = (int)$request['course_code'];

    if ($course_code <= 0) {
        throw new Exception("رقم المهارة غير صحيح");
    }

    $stmt = $conn->prepare("SELECT course_code FROM course WHERE course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception("المهارة غير موجودة أو تم حذفها مسبقًا");
    }

    $stmt->close();

    $stmt = $conn->prepare("DELETE pe FROM pre_exam pe INNER JOIN chapter ch ON pe.chapter_code = ch.chapter_code WHERE ch.course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE cq FROM chapter_quiz cq INNER JOIN chapter ch ON cq.chapter_code = ch.chapter_code WHERE ch.course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM certificate WHERE course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM end_exam WHERE course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM student_course WHERE course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM chapter WHERE course_code = ?");
    $stmt->bind_param("i", $course_code);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM course WHERE course_code = ?");
    $stmt->bind_param("i", $course_code);

    if (!$stmt->execute()) {
        throw new Exception("فشل حذف المهارة");
    }

    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE course_request
        SET status = 'approved',
            reviewed_by_admin_id = ?,
            reviewed_at = NOW()
        WHERE request_id = ?
    ");

    $request_id = (int)$request['request_id'];
    $stmt->bind_param("ii", $admin_id, $request_id);

    if (!$stmt->execute()) {
        throw new Exception("فشل تحديث حالة الطلب");
    }

    $stmt->close();
}

function rejectRequest($conn, $request, $admin_id) {
    $request_id = (int)$request['request_id'];

    if ($request['request_type'] === 'add') {
        $payload = decodePayload($request['payload']);

        if (!empty($payload['course_image']) && file_exists($payload['course_image'])) {
            unlink($payload['course_image']);
        }
    }

    $stmt = $conn->prepare("
        UPDATE course_request
        SET status = 'rejected',
            reviewed_by_admin_id = ?,
            reviewed_at = NOW()
        WHERE request_id = ?
    ");

    $stmt->bind_param("ii", $admin_id, $request_id);

    if (!$stmt->execute()) {
        throw new Exception("فشل رفض الطلب");
    }

    $stmt->close();
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';

    if ($request_id <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
        $error = "طلب غير صحيح";
    } else {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("
                SELECT *
                FROM course_request
                WHERE request_id = ? AND status = 'pending'
                FOR UPDATE
            ");

            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows !== 1) {
                throw new Exception("الطلب غير موجود أو تمت مراجعته مسبقًا");
            }

            $request = $result->fetch_assoc();
            $stmt->close();

            if ($decision === 'reject') {
                rejectRequest($conn, $request, $admin_id);
                $message = "تم رفض الطلب بنجاح";
            } else {
                if ($request['request_type'] === 'add') {
                    approveAddRequest($conn, $request, $admin_id);
                    $message = "تمت الموافقة على إضافة المهارة";
                } elseif ($request['request_type'] === 'update') {
                    approveUpdateRequest($conn, $request, $admin_id);
                    $message = "تمت الموافقة على تعديل المهارة";
                } elseif ($request['request_type'] === 'delete') {
                    approveDeleteRequest($conn, $request, $admin_id);
                    $message = "تمت الموافقة على حذف المهارة";
                } else {
                    throw new Exception("نوع الطلب غير معروف");
                }
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$statusFilter = $_GET['status'] ?? 'pending';

if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}

if ($statusFilter === 'all') {
    $stmt = $conn->prepare("
        SELECT cr.*, 
               CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
               t.email AS teacher_email,
               c.title AS current_course_title
        FROM course_request cr
        INNER JOIN teacher t ON cr.teacher_id = t.teacher_id
        LEFT JOIN course c ON cr.course_code = c.course_code
        ORDER BY cr.created_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT cr.*, 
               CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
               t.email AS teacher_email,
               c.title AS current_course_title
        FROM course_request cr
        INNER JOIN teacher t ON cr.teacher_id = t.teacher_id
        LEFT JOIN course c ON cr.course_code = c.course_code
        WHERE cr.status = ?
        ORDER BY cr.created_at DESC
    ");

    $stmt->bind_param("s", $statusFilter);
}

$stmt->execute();
$requests = $stmt->get_result();
$stats = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$statsResult = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM course_request
    GROUP BY status
");

if ($statsResult) {
    while ($row = $statsResult->fetch_assoc()) {
        $stats[$row['status']] = (int)$row['total'];
        $stats['all'] += (int)$row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة طلبات المسؤول</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <style>
        :root {
            --primary: #2e77c0;
            --primary-dark: #255f9b;
            --primary-soft: #eaf3fc;
            --bg: #f4f8fc;
            --white: #ffffff;
            --text: #16324f;
            --muted: #6b7c90;
            --border: #dbe6f1;
            --shadow: 0 18px 45px rgba(22, 50, 79, 0.12);
            --green: #16a34a;
            --red: #dc2626;
            --yellow: #f59e0b;
            --blue: #2563eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Cairo", sans-serif;
        }

        body {
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top right, rgba(46, 119, 192, 0.16), transparent 30%),
                linear-gradient(180deg, #f7fbff 0%, #eef3f8 100%);
        }

        .admin-page {
            width: min(1240px, 92%);
            margin: 0 auto;
            padding: 34px 0 70px;
        }

        .admin-top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .admin-return,
        .admin-logout {
            min-height: 44px;
            padding: 0 18px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 900;
            transition: 0.25s ease;
        }

        .admin-return {
            background: white;
            color: var(--primary);
            border: 1px solid var(--border);
            box-shadow: 0 8px 20px rgba(22, 50, 79, 0.07);
        }

        .admin-return:hover {
            background: var(--primary-soft);
            transform: translateY(-2px);
        }

        .admin-logout {
            background: #111827;
            color: white;
            border: 1px solid #111827;
        }

        .admin-logout:hover {
            background: #000;
            transform: translateY(-2px);
        }

        .admin-hero {
            position: relative;
            overflow: hidden;
            min-height: 250px;
            border-radius: 34px;
            padding: 42px 46px;
            background:
                linear-gradient(135deg, rgba(46, 119, 192, 0.98), rgba(61, 141, 224, 0.94)),
                radial-gradient(circle at left top, rgba(255, 255, 255, 0.28), transparent 34%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 28px;
            box-shadow: 0 22px 55px rgba(46, 119, 192, 0.25);
        }

        .admin-hero::before {
            content: "";
            position: absolute;
            width: 330px;
            height: 330px;
            border-radius: 50%;
            left: -100px;
            top: -130px;
            background: rgba(255,255,255,0.16);
        }

        .admin-hero::after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            right: 42%;
            bottom: -160px;
            background: rgba(255,255,255,0.11);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 740px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.28);
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .admin-hero h1 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .admin-hero p {
            max-width: 720px;
            font-size: 18px;
            line-height: 2;
            color: rgba(255,255,255,0.92);
        }

        .hero-icon {
            position: relative;
            z-index: 2;
            width: 128px;
            height: 128px;
            border-radius: 32px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.26);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .hero-icon .material-symbols-outlined {
            font-size: 72px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin: 28px 0;
        }

        .stat-card {
            background: rgba(255,255,255,0.9);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 12px 30px rgba(22, 50, 79, 0.08);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon .material-symbols-outlined {
            font-size: 29px;
        }

        .stat-card strong {
            display: block;
            font-size: 28px;
            color: #111827;
            line-height: 1;
        }

        .stat-card span {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .stat-all .stat-icon {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .stat-pending .stat-icon {
            color: #92400e;
            background: #fef3c7;
        }

        .stat-approved .stat-icon {
            color: #166534;
            background: #dcfce7;
        }

        .stat-rejected .stat-icon {
            color: #991b1b;
            background: #fee2e2;
        }

        .requests-panel {
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(219,230,241,0.95);
            border-radius: 34px;
            padding: 32px;
            box-shadow: 0 14px 35px rgba(22, 50, 79, 0.09);
            backdrop-filter: blur(14px);
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .section-label {
            color: var(--primary);
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .panel-header h2 {
            color: #111827;
            font-size: 30px;
            font-weight: 900;
        }

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filters a {
            text-decoration: none;
            min-height: 42px;
            padding: 0 16px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            color: var(--primary);
            background: white;
            border: 1px solid var(--border);
            font-size: 14px;
            font-weight: 900;
            transition: 0.25s ease;
        }

        .filters a:hover {
            background: var(--primary-soft);
            transform: translateY(-2px);
        }

        .filters a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 10px 22px rgba(46, 119, 192, 0.24);
        }

        .notice {
            padding: 14px 18px;
            border-radius: 18px;
            margin-bottom: 18px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notice.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .notice.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .request-card {
            position: relative;
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 12px 28px rgba(22, 50, 79, 0.08);
            transition: 0.28s ease;
        }

        .request-card::before {
            content: "";
            position: absolute;
            inset: 0;
            height: 7px;
            background: linear-gradient(90deg, var(--primary), #60a5fa, #93c5fd);
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 22px 48px rgba(22, 50, 79, 0.14);
            border-color: rgba(46,119,192,0.35);
        }

        .request-head {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            border-bottom: 1px solid #eef2f7;
            padding-bottom: 18px;
            margin-bottom: 18px;
        }

        .badges-row {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 13px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .badge-add {
            background: #dcfce7;
            color: #166534;
        }

        .badge-update {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .request-number {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 900;
        }

        .info-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .info-box {
            background: #f7fbff;
            border: 1px solid #e5eef8;
            padding: 14px;
            border-radius: 18px;
            min-height: 80px;
        }

        .info-box strong {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 6px;
            font-weight: 900;
        }

        .info-box span {
            display: block;
            color: var(--text);
            font-size: 14px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .payload-box {
            position: relative;
            z-index: 2;
            background: #f8fbff;
            border: 1px solid #e5eef8;
            border-radius: 22px;
            padding: 18px;
            line-height: 1.9;
        }

        .payload-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #111827;
            font-size: 16px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .payload-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .payload-item {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 16px;
            padding: 12px 14px;
        }

        .payload-item b {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .payload-item span {
            color: var(--text);
            font-size: 14px;
            font-weight: 900;
        }

        .payload-long {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 16px;
            padding: 14px;
            margin-top: 12px;
        }

        .payload-long b {
            display: block;
            color: var(--primary);
            font-size: 13px;
            margin-bottom: 7px;
            font-weight: 900;
        }

        .payload-long p {
            color: #334155;
            font-size: 14px;
            line-height: 1.95;
            max-height: 145px;
            overflow-y: auto;
            padding-left: 8px;
        }

        .payload-image {
            width: 210px;
            height: 125px;
            object-fit: cover;
            border-radius: 18px;
            border: 1px solid #dbe6f1;
            box-shadow: 0 10px 22px rgba(22, 50, 79, 0.12);
            margin-top: 10px;
        }

        .actions {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        .actions button {
            min-width: 130px;
            height: 44px;
            border: none;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: 0.25s ease;
        }

        .actions button:hover {
            transform: translateY(-2px);
        }

        .approve {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: white;
            box-shadow: 0 10px 22px rgba(22, 163, 74, 0.22);
        }

        .reject {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            box-shadow: 0 10px 22px rgba(220, 38, 38, 0.22);
        }

        .empty {
            min-height: 340px;
            border-radius: 28px;
            background: linear-gradient(180deg, white, #f7fbff);
            border: 1px dashed #bfd3e8;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 40px 24px;
            color: var(--muted);
        }

        .empty .material-symbols-outlined {
            width: 92px;
            height: 92px;
            border-radius: 28px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin-bottom: 18px;
        }

        .empty h3 {
            color: #111827;
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .empty p {
            font-size: 16px;
            font-weight: 700;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payload-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .admin-page {
                width: 92%;
                padding: 24px 0 60px;
            }

            .admin-top-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-return,
            .admin-logout {
                width: 100%;
            }

            .admin-hero {
                padding: 32px 24px;
                border-radius: 26px;
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-hero h1 {
                font-size: 32px;
            }

            .admin-hero p {
                font-size: 16px;
            }

            .hero-icon {
                width: 96px;
                height: 96px;
                border-radius: 26px;
            }

            .hero-icon .material-symbols-outlined {
                font-size: 54px;
            }

            .stats-grid,
            .info-grid,
            .payload-grid {
                grid-template-columns: 1fr;
            }

            .requests-panel {
                padding: 22px;
                border-radius: 26px;
            }

            .panel-header {
                flex-direction: column;
            }

            .filters a {
                flex: 1;
            }

            .actions button {
                width: 100%;
            }

            .actions form {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<main class="admin-page">

    <div class="admin-top-actions">
        <a href="second.php" class="admin-return">
            <span class="material-symbols-outlined">arrow_forward</span>
            العودة للرئيسية
        </a>

        <a href="logout.php" class="admin-logout">
            <span class="material-symbols-outlined">logout</span>
            تسجيل خروج
        </a>
    </div>

    <section class="admin-hero">
        <div class="hero-content">
            <span class="hero-badge">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                لوحة المسؤول
            </span>

            <h1>إدارة طلبات المهارات</h1>

            <p>
                راجع طلبات المعلمين الخاصة بإضافة المهارات أو تعديلها أو حذفها، واتخذ القرار المناسب من لوحة احترافية واضحة ومنظمة.
            </p>
        </div>

        <div class="hero-icon">
            <span class="material-symbols-outlined">rule_settings</span>
        </div>
    </section>

    <section class="stats-grid">
        <div class="stat-card stat-all">
            <div class="stat-icon">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div>
                <strong><?php echo (int)$stats['all']; ?></strong>
                <span>كل الطلبات</span>
            </div>
        </div>

        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <span class="material-symbols-outlined">pending_actions</span>
            </div>
            <div>
                <strong><?php echo (int)$stats['pending']; ?></strong>
                <span>طلبات معلقة</span>
            </div>
        </div>

        <div class="stat-card stat-approved">
            <div class="stat-icon">
                <span class="material-symbols-outlined">verified</span>
            </div>
            <div>
                <strong><?php echo (int)$stats['approved']; ?></strong>
                <span>طلبات مقبولة</span>
            </div>
        </div>

        <div class="stat-card stat-rejected">
            <div class="stat-icon">
                <span class="material-symbols-outlined">cancel</span>
            </div>
            <div>
                <strong><?php echo (int)$stats['rejected']; ?></strong>
                <span>طلبات مرفوضة</span>
            </div>
        </div>
    </section>

    <section class="requests-panel">

        <?php if ($message !== ""): ?>
            <div class="notice success">
                <span class="material-symbols-outlined">check_circle</span>
                <?php echo safeText($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="notice error">
                <span class="material-symbols-outlined">error</span>
                <?php echo safeText($error); ?>
            </div>
        <?php endif; ?>

        <div class="panel-header">
            <div>
                <p class="section-label">Requests Board</p>
                <h2>قائمة الطلبات</h2>
            </div>

            <div class="filters">
                <a class="<?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="admin_requests.php?status=pending">
                    <span class="material-symbols-outlined">pending</span>
                    معلقة
                </a>

                <a class="<?php echo $statusFilter === 'approved' ? 'active' : ''; ?>" href="admin_requests.php?status=approved">
                    <span class="material-symbols-outlined">check_circle</span>
                    مقبولة
                </a>

                <a class="<?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" href="admin_requests.php?status=rejected">
                    <span class="material-symbols-outlined">cancel</span>
                    مرفوضة
                </a>

                <a class="<?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="admin_requests.php?status=all">
                    <span class="material-symbols-outlined">apps</span>
                    الكل
                </a>
            </div>
        </div>

        <?php if ($requests->num_rows === 0): ?>
            <div class="empty">
                <span class="material-symbols-outlined">inbox</span>
                <h3>لا توجد طلبات حاليًا</h3>
                <p>لا توجد طلبات في هذا القسم. جرّب تغيير الفلتر من الأعلى.</p>
            </div>
        <?php endif; ?>

        <?php while ($request = $requests->fetch_assoc()): ?>
            <?php
                $payload = decodePayload($request['payload']);

                $typeText = [
                    'add' => 'طلب إضافة مهارة',
                    'update' => 'طلب تعديل مهارة',
                    'delete' => 'طلب حذف مهارة'
                ][$request['request_type']] ?? 'طلب غير معروف';

                $typeClass = [
                    'add' => 'badge-add',
                    'update' => 'badge-update',
                    'delete' => 'badge-delete'
                ][$request['request_type']] ?? '';

                $statusText = [
                    'pending' => 'معلق',
                    'approved' => 'مقبول',
                    'rejected' => 'مرفوض'
                ][$request['status']] ?? $request['status'];

                $statusClass = 'badge-' . $request['status'];
            ?>

            <article class="request-card">

                <div class="request-head">
                    <div class="badges-row">
                        <span class="badge <?php echo $typeClass; ?>">
                            <?php echo safeText($typeText); ?>
                        </span>

                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo safeText($statusText); ?>
                        </span>
                    </div>

                    <div class="request-number">
                        <span class="material-symbols-outlined">tag</span>
                        رقم الطلب #<?php echo (int)$request['request_id']; ?>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <strong>المعلم</strong>
                        <span><?php echo safeText($request['teacher_name']); ?></span>
                    </div>

                    <div class="info-box">
                        <strong>إيميل المعلم</strong>
                        <span><?php echo safeText($request['teacher_email']); ?></span>
                    </div>

                    <div class="info-box">
                        <strong>المهارة الحالية</strong>
                        <span><?php echo safeText($request['current_course_title'] ?: 'مهارة جديدة'); ?></span>
                    </div>

                    <div class="info-box">
                        <strong>تاريخ الطلب</strong>
                        <span><?php echo safeText($request['created_at']); ?></span>
                    </div>
                </div>

                <div class="payload-box">

                    <?php if ($request['request_type'] === 'delete'): ?>

                        <div class="payload-title">
                            <span class="material-symbols-outlined">delete</span>
                            تفاصيل طلب الحذف
                        </div>

                        <div class="payload-long">
                            <b>وصف الطلب</b>
                            <p>
                                المعلم يطلب حذف المهارة:
                                <strong><?php echo safeText($request['current_course_title'] ?: 'غير متوفرة'); ?></strong>
                            </p>
                        </div>

                        <?php if (!empty($payload['message'])): ?>
                            <div class="payload-long">
                                <b>رسالة الطلب</b>
                                <p><?php echo safeText($payload['message']); ?></p>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>

                        <div class="payload-title">
                            <span class="material-symbols-outlined">description</span>
                            بيانات المهارة المقترحة
                        </div>

                        <div class="payload-grid">
                            <div class="payload-item">
                                <b>اسم المهارة</b>
                                <span><?php echo safeText($payload['title'] ?? ''); ?></span>
                            </div>

                            <div class="payload-item">
                                <b>عدد الأسابيع</b>
                                <span><?php echo safeText($payload['weeks'] ?? ''); ?></span>
                            </div>

                            <div class="payload-item">
                                <b>عدد الساعات</b>
                                <span><?php echo safeText($payload['time'] ?? ''); ?></span>
                            </div>

                            <div class="payload-item">
                                <b>عدد الوحدات المهارية</b>
                                <span><?php echo safeText($payload['total_chapters'] ?? ''); ?></span>
                            </div>

                            <div class="payload-item">
                                <b>نوع الطلب</b>
                                <span><?php echo safeText($typeText); ?></span>
                            </div>

                            <div class="payload-item">
                                <b>حالة الطلب</b>
                                <span><?php echo safeText($statusText); ?></span>
                            </div>
                        </div>

                        <div class="payload-long">
                            <b>الوصف</b>
                            <p><?php echo nl2br(safeText($payload['description'] ?? '')); ?></p>
                        </div>

                        <div class="payload-long">
                            <b>سوف يتعلم الطالب</b>
                            <p><?php echo nl2br(safeText($payload['steps'] ?? '')); ?></p>
                        </div>

                        <div class="payload-long">
                            <b>محتوى المهارة</b>
                            <p><?php echo nl2br(safeText($payload['content'] ?? '')); ?></p>
                        </div>

                        <?php if (!empty($payload['course_image'])): ?>
                            <div class="payload-long">
                                <b>صورة المهارة</b>
                                <img 
                                    class="payload-image"
                                    src="<?php echo safeText($payload['course_image']); ?>" 
                                    alt="صورة المهارة"
                                >
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

                <?php if ($request['status'] === 'pending'): ?>
                    <div class="actions">
                        <form method="post" onsubmit="return confirm('هل أنت متأكد من الموافقة على هذا الطلب؟');">
                            <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                            <input type="hidden" name="decision" value="approve">
                            <button type="submit" class="approve">
                                <span class="material-symbols-outlined">check</span>
                                موافقة
                            </button>
                        </form>

                        <form method="post" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب؟');">
                            <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                            <input type="hidden" name="decision" value="reject">
                            <button type="submit" class="reject">
                                <span class="material-symbols-outlined">close</span>
                                رفض
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

            </article>

        <?php endwhile; ?>

    </section>

</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>