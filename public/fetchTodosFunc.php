<?php
function fetchTodos($pdo) {
    $query = $pdo->prepare("SELECT * FROM todo");
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}