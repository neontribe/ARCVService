<?php

if (! is_writeable("/opt/project/.env")) {
    echo "Can't write to .env file\n";
    exit(1);
}

$contents = file_get_contents("/opt/project/.env");
if (getenv("APP_ENV") == "prod" && strpos($contents, "PASSWORD_CLIENT_SECRET")) {
    echo "PASSWORD_CLIENT_SECRET exists and env is production, not overwriting\n";
    exit(0);
}

$lines = explode("\n", $contents);
$cleaned = [];
print_r($lines);
foreach ($lines as $line) {
    if (!strpos($line, "PASSWORD_CLIENT") || !strpos($line, "PASSWORD_CLIENT_SECRET")) {
        $cleaned[] = $line;
    }
}

$output = [];

exec('php artisan passport:keys --force');
exec("php artisan passport:client --password --name '" . getenv("APP_NAME") . " Password Grant Client' --provider=users", $output);
print_r($output);
foreach ($output as $line) {
    if (str_starts_with($line, "Client ID")) {
        $elements = explode(" ", $line);
        $cleaned[] = "PASSWORD_CLIENT=" . $elements[2];
    }
    if (str_starts_with($line, "Client secret")) {
        $elements = explode(" ", $line);
        $cleaned[] = "PASSWORD_CLIENT_SECRET=" . $elements[2];
    }
}
exec("chmod 600 /opt/project/storage/*.key");

file_put_contents("/opt/project/.env", implode("\n", $lines + $cleaned));