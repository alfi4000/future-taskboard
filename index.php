<?php
// Connect to MariaDB
$host = 'localhost';
$user = 'test';
$pass = 'test';
$db = 'task_board';
$conn = new mysqli($host, $user, $pass, $db);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the table exists, and if not, create it
$table_check = $conn->query("SHOW TABLES LIKE 'tasks'");
if ($table_check->num_rows == 0) {
    $conn->query("CREATE TABLE tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title TEXT NOT NULL,
        description TEXT,
        images TEXT,
        completed BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Handle form submission to add a task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Handle image uploads
    $imageNames = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $target_file = "uploads/" . basename($file_name);
            if (move_uploaded_file($tmp_name, $target_file)) {
                $imageNames[] = $file_name;
            }
        }
    }
    $images = implode(',', $imageNames);

    // Save task to the database
    $stmt = $conn->prepare("INSERT INTO tasks (title, description, images) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $description, $images);
    $stmt->execute();

    // Redirect to the same page to prevent form resubmission
    header("Location: index.php");
    exit;
}

// Fetch all tasks from the database
$tasks = $conn->query("SELECT * FROM tasks");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flug Preise</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1c1c1c;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        .task-board {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 50px auto;
            max-width: 1200px;
        }
        .task-input {
            width: 100%;
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
        }
        .task-input input, .task-input textarea {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #444;
            background-color: #333;
            color: #fff;
            border-radius: 5px;
        }
        .task-input button {
            padding: 10px;
            background-color: #00c1ff;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .task-input button:hover {
            background-color: #0098d4;
        }
        .task-list {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .task-box {
            background-color: #333;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .task-box h3 {
            margin-top: 0;
        }
        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
        }
        .task-controls {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .task-controls button {
            background-color: #444;
            border: none;
            color: white;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .task-controls button.complete-task {
            background-color: #00ff85;
        }
        .task-controls button.complete-task:hover {
            background-color: #00cc66;
        }
        .task-controls button.delete-task {
            background-color: #ff5757;
        }
        .task-controls button.delete-task:hover {
            background-color: #cc4d4d;
        }
        #edit-form {
            display: none;
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -20%);
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 80%;
            max-height: 80%;
            overflow-y: auto;
        }
        #edit-form input, #edit-form textarea {
            margin-bottom: 10px;
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            background-color: #333;
            color: #fff;
            border-radius: 5px;
        }
        #image-previews img {
            width: 100px; /* Thumbnail size */
            margin-right: 10px;
            cursor: pointer;
        }
        #image-previews .image-container {
            position: relative;
            display: inline-block;
            margin-right: 40px; /* Increase right margin */
            margin-bottom: 10px;
        }
        #image-previews .delete-image {
            position: absolute;
            top: -10px;
            right: -20px;
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
        }
        #success-message {
            display: none;
            background-color: #00ff85;
            color: black;
            padding: 10px;
            border-radius: 5px;
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translate(-50%, -20%);
            z-index: 1000;
        }
        .task-box.completed {
            background-color: #2a3a2a;
        }
        .task-box .complete-status {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            color: #00ff85;
        }
        .title {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 20px;
            animation: fadeIn 2s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .image-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1001;
            text-align: center;
            padding: 20px;
        }
        .image-popup img {
            max-width: 80%;
            max-height: 80%;
            border-radius: 5px;
        }
        .image-popup .close-button {
            position: absolute;
            top: 10px;
            right: 60px;
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 18px;
            cursor: pointer;
        }
        @media (max-width: 600px) {
            .title {
                font-size: 2rem;
            }
            .task-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="task-board">
        <h1 class="title">FLUG PREISE</h1>
        <!-- Add Task Form -->
        <form action="index.php" method="post" enctype="multipart/form-data">
            <div class="task-input">
                <input type="text" name="title" placeholder="Fluggeselschaft" required>
                <textarea name="description" placeholder="Flug Beschreibung (10 linien maximal)" rows="10"></textarea>
                <input type="file" name="images[]" multiple>
                <button type="submit" name="add_task">Flug Hinzufügen</button>
            </div>
        </form>

        <!-- Task List -->
        <div class="task-list">
            <?php while ($task = $tasks->fetch_assoc()) : ?>
                <div class="task-box <?php echo $task['completed'] ? 'completed' : ''; ?>">
                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    <?php if ($task['completed']) : ?>
                        <span class="complete-status">✓</span>
                    <?php endif; ?>

                    <!-- Image preview -->
                    <?php if (!empty($task['images'])) : ?>
                        <div class="image-preview">
                            <?php $image_files = explode(',', $task['images']);
                            foreach ($image_files as $image) {
                                echo "<img src='uploads/$image' alt='$image' onclick='openImagePreview(\"uploads/$image\")' />";
                            } ?>
                        </div>
                    <?php endif; ?>

                    <!-- Task Controls -->
                    <div class="task-controls">
                        <button class="complete-task" data-id="<?php echo $task['id']; ?>">
                            <?php echo $task['completed'] ? 'Unmark' : 'Mark Complete'; ?>
                        </button>
                        <button class="edit-task" data-id="<?php echo $task['id']; ?>" onclick="openEditForm(<?php echo $task['id']; ?>)">Edit</button>
                        <button class="delete-task" data-id="<?php echo $task['id']; ?>">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Edit Form -->
    <div id="edit-form">
        <h2>Edit Task</h2>
        <form id="edit-task-form" enctype="multipart/form-data">
            <input type="hidden" name="edit_task_id" id="edit-task-id">
            <input type="text" name="edit_title" id="edit-title" placeholder="Task Title" required>
            <textarea name="edit_description" id="edit-description" placeholder="Task Description (10 lines max)" rows="10"></textarea>
            <input type="file" name="edit_images[]" multiple>
            <button type="submit">Save Changes</button>
        </form>
        <button onclick="closeEditForm()">Cancel</button>
        <div id="image-previews"></div>
    </div>

    <!-- Success Message -->
    <div id="success-message">Task successfully updated!</div>

    <!-- Image Popup -->
    <div class="image-popup" id="image-popup">
        <button class="close-button" onclick="closeImagePopup()">×</button>
        <img src="" alt="Image Preview" id="popup-image">
    </div>

    <!-- JavaScript embedded in the HTML -->
    <script>
        function openImagePreview(src) {
            const popup = document.getElementById('image-popup');
            const popupImage = document.getElementById('popup-image');
            popupImage.src = src;
            popup.style.display = 'block';
        }

        function closeImagePopup() {
            const popup = document.getElementById('image-popup');
            popup.style.display = 'none';
        }

        function closeEditForm() {
            document.querySelector('#edit-form').style.display = 'none';
        }

        document.querySelectorAll('.complete-task').forEach(button => {
            button.addEventListener('click', (e) => {
                const taskId = e.target.getAttribute('data-id');
                fetch(`task_actions.php?action=complete&id=${taskId}`)
                    .then(response => response.text())
                    .then(data => {
                        const taskBox = e.target.closest('.task-box');
                        taskBox.classList.toggle('completed');
                        const completeStatus = taskBox.querySelector('.complete-status');
                        if (completeStatus) {
                            completeStatus.remove();
                        } else {
                            taskBox.insertAdjacentHTML('afterbegin', '<span class="complete-status">✓</span>');
                        }
                        e.target.textContent = e.target.textContent === 'Mark Complete' ? 'Unmark' : 'Mark Complete';

                        // Reload the page after a short delay to show the UI update first
                        setTimeout(() => {
                            location.reload();
                        }, 500); // 500 milliseconds delay
                    })
                    .catch(error => console.error('Error:', error));
            });
        });

        document.querySelectorAll('.delete-task').forEach(button => {
            button.addEventListener('click', (e) => {
                const taskId = e.target.getAttribute('data-id');
                if (confirm("Are you sure you want to delete this task?")) {
                    fetch(`task_actions.php?action=delete&id=${taskId}`)
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            location.reload(); // Reload the page to remove the deleted task
                        });
                }
            });
        });

        function openEditForm(taskId) {
            fetch(`task_actions.php?action=get_task&id=${taskId}`)
                .then(response => response.json())
                .then(task => {
                    document.querySelector('#edit-title').value = task.title;
                    document.querySelector('#edit-description').value = task.description;
                    document.querySelector('#edit-task-id').value = taskId;
                    document.querySelector('#edit-form').style.display = 'block';

                    // Clear and set image previews
                    const previewContainer = document.querySelector('#image-previews');
                    previewContainer.innerHTML = ''; // Clear existing previews
                    const images = task.images.split(',').filter(img => img.trim() !== '');
                    images.forEach(image => {
                        const imageContainer = document.createElement('div');
                        imageContainer.className = 'image-container';

                        const img = document.createElement('img');
                        img.src = `uploads/${image}`;
                        img.style.width = '100px'; // Thumbnail size
                        img.addEventListener('click', () => openImagePreview(`uploads/${image}`));

                        const deleteBtn = document.createElement('button');
                        deleteBtn.textContent = 'x';
                        deleteBtn.className = 'delete-image';
                        deleteBtn.setAttribute('data-image', image);
                        deleteBtn.addEventListener('click', () => {
                            imageContainer.remove();
                        });

                        imageContainer.appendChild(img);
                        imageContainer.appendChild(deleteBtn);
                        previewContainer.appendChild(imageContainer);
                    });
                });
        }

        document.querySelector('#edit-task-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            // Collect images to delete
            const imagesToDelete = [];
            document.querySelectorAll('#image-previews .delete-image').forEach(button => {
                imagesToDelete.push(button.getAttribute('data-image'));
            });
            formData.append('images_to_delete', JSON.stringify(imagesToDelete));

            fetch('task_actions.php', { method: 'POST', body: formData })
                .then(response => response.text())
                .then(data => {
                    document.querySelector('#success-message').style.display = 'block';
                    setTimeout(() => {
                        document.querySelector('#success-message').style.display = 'none';
                        closeEditForm();
                        location.reload();
                    }, 2000); // Show message for 2 seconds
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>


