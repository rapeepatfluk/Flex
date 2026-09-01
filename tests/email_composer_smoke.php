<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$unsafeTitle = '<script>alert("x")</script>';
$body = '<p>Allowed email body</p>';
$html = email_layout($unsafeTitle, $body);

if (str_contains($html, $unsafeTitle)) {
    throw new RuntimeException('Email title was not escaped in the HTML layout');
}
if (!str_contains($html, '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;')) {
    throw new RuntimeException('Escaped email title is missing from the HTML layout');
}
if (!str_contains($html, $body)) {
    throw new RuntimeException('Email body was removed from the HTML layout');
}

$parameters = (new ReflectionFunction('send_mail'))->getParameters();
if (count($parameters) !== 6 || !$parameters[4]->isOptional() || !$parameters[5]->isOptional()) {
    throw new RuntimeException('Reply-To parameters are not backward compatible');
}

echo "email composer smoke test: PASS\n";
