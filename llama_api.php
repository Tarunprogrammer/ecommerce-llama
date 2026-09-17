<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input['message'] ?? '';

if (!$userMessage) {
    echo json_encode(["reply" => "Please ask something about products 😊"]);
    exit;
}

$data = [
    "model" => "llama3",
    "prompt" => "You are an AI assistant for an e-commerce website. Answer clearly and briefly.\nUser: $userMessage\nAI:",
    "stream" => false
];

$ch = curl_init("http://localhost:11434/api/generate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
echo json_encode([
    "reply" => $result['response'] ?? "Sorry, I couldn't understand."
]);
