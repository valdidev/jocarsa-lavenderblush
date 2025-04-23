<?php
header("Content-Type: text/plain");

$data = file_get_contents("php://input");
$classes = json_decode($data, true);

if (null === $classes) {
    http_response_code(400);
    echo "Invalid JSON data.";
    exit;
}

$dir = 'classes';

if (!is_dir($dir)) mkdir($dir, 0755, true);

foreach ($classes as $classData) {
    $className = trim($classData['className']);

    $sanitizedClassName = preg_replace('/[^A-Za-z0-9_]/', '', $className);
    if (empty($sanitizedClassName)) {
        $sanitizedClassName = 'UnnamedClass';
    }

    $fileName = $dir . '/' . $sanitizedClassName . '.php';

    $classCode = "<?php\n";
    $classCode .= "class $sanitizedClassName {\n\n";

    if (!empty($classData['properties'])) {
        foreach ($classData['properties'] as $property) {
            $propertyName = trim($property);
            $propertyNameSanitized = preg_replace('/[^A-Za-z0-9_]/', '', $propertyName);
            if (empty($propertyNameSanitized)) {
                continue;
            }
            $classCode .= "    public \$$propertyNameSanitized;\n";
        }
        $classCode .= "\n";
    }

    if (!empty($classData['methods'])) {
        foreach ($classData['methods'] as $method) {
            $methodName = trim($method);
            $methodNameSanitized = preg_replace('/[^A-Za-z0-9_]/', '', $methodName);
            if (empty($methodNameSanitized)) {
                continue;
            }
            $classCode .= "    public function $methodNameSanitized() {\n";
            $classCode .= "        \n";
            $classCode .= "    }\n\n";
        }
    }

    $classCode .= "}\n";

    file_put_contents($fileName, $classCode);
}

echo "Classes saved successfully.";
