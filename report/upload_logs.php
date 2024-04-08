<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key      = ($_POST['key']    ?? '');
    $status   = ($_POST['status'] ?? '');
    $validKey = ($key === 'KgwGdGamVq9ak4f6xzZw');

    if (
        $validKey and

        (
            ($status === 'success') ||
            ($status === 'error')
        )
    ) {
        $directory = (($status === 'success') ? 'tmp/success' : 'tmp/error');
        if (!file_exists($directory)) mkdir($directory, 0777, true);

        if (
            isset($_FILES['file']) and
            ($_FILES['file']['error'] === UPLOAD_ERR_OK)
        ) {
            $uploadedFilePath = $directory . '/' . basename($_FILES['file']['name']);
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadedFilePath);
            echo "File uploaded successfully.";
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo "Invalid key or status.";
    }
} else {
    echo "Method not allowed.";
}
