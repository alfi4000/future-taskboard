<?php
// Connect to MariaDB
$host = 'localhost';
$user = 'view-server';
$pass = 'q8Jc5xUrGE0m9Rh';
$db = 'task_board_2';
$conn = new mysqli($host, $user, $pass, $db);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle different actions
$action = $_GET['action'] ?? '';
$taskId = $_GET['id'] ?? '';

if ($action == 'complete') {
    // Toggle the completed status of a task
    $stmt = $conn->prepare("UPDATE tasks SET completed = NOT completed WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    echo "Task status updated.";
} elseif ($action == 'delete') {
    // Delete a task and its associated images
    $stmt = $conn->prepare("SELECT images FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $images = explode(',', $task['images']);
    foreach ($images as $image) {
        if (file_exists("uploads/$image")) {
            unlink("uploads/$image");
        }
    }
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    echo "Task deleted.";
} elseif ($action == 'get_task') {
    // Fetch task details for editing
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    echo json_encode($task);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission to update a task
    $taskId = $_POST['edit_task_id'];
    $title = $_POST['edit_title'];
    $description = $_POST['edit_description'];

    // Handle image uploads
    $imageNames = [];
    if (!empty($_FILES['edit_images']['name'][0])) {
        foreach ($_FILES['edit_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['edit_images']['name'][$key];
            $target_file = "uploads/" . basename($file_name);
            if (move_uploaded_file($tmp_name, $target_file)) {
                $imageNames[] = $file_name;
            }
        }
    }
    $images = implode(',', $imageNames);

    // Handle image deletions
    $imagesToDelete = json_decode($_POST['images_to_delete'], true);
    if (!empty($imagesToDelete)) {
        $stmt = $conn->prepare("SELECT images FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();
        $task = $result->fetch_assoc();
        $imagesArray = explode(',', $task['images']);
        $imagesArray = array_filter($imagesArray, function($img) use ($imagesToDelete) {
            return !in_array($img, $imagesToDelete);
        });
        $updatedImages = implode(',', $imagesArray);
        $stmt = $conn->prepare("UPDATE tasks SET images = ? WHERE id = ?");
        $stmt->bind_param("si", $updatedImages, $taskId);
        $stmt->execute();
    }

    // Update task in the database
    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, images = CONCAT_WS(',', ?, images) WHERE id = ?");
    $stmt->bind_param("sssi", $title, $description, $images, $taskId);
    $stmt->execute();
    echo "Task updated.";
}

$conn->close();
?>
