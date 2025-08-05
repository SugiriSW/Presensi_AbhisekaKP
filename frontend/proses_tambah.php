<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Data berhasil dikirim:<br>";
    echo "ID User: " . $_POST['id_user'] . "<br>";
    echo "Username: " . $_POST['username'] . "<br>";
    echo "Password: " . $_POST['password'] . "<br>";
    echo "UID: " . $_POST['uid'] . "<br>";
}
?>
