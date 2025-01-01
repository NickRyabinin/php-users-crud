<?php

/**
 * Класс Captcha предоставляет приложению методы для генерации изображения
 * с буквенно-цифровой капчей и работы с текстом капчи.
 */

namespace src;

class Captcha
{
    private int $width;
    private int $height;
    private int $fontSize;
    private string $fontPath;
    private string $captchaText;
    private const CAPTCHA_LENGTH = 4;

    public function __construct(string $fontPath, int $width = 200, int $height = 80, int $fontSize = 30)
    {
        $this->width = $width;
        $this->height = $height;
        $this->fontSize = $fontSize;
        $this->fontPath = $fontPath;
    }

    private function generateRandomText(int $length = self::CAPTCHA_LENGTH): void
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $this->captchaText = '';
        for ($i = 0; $i < $length; $i++) {
            $this->captchaText .= $characters[rand(0, strlen($characters) - 1)];
        }
        $_SESSION['captcha_text'] = $this->captchaText;
    }

    public function createCaptcha(): void
    {
        $this->generateRandomText();

        $image = imagecreatetruecolor($this->width, $this->height);
        $backgroundColor = (int) imagecolorallocate($image, 255, 255, 255); // белый фон
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $backgroundColor);

        // Добавляем лёгкие шумы
        for ($i = 0; $i < 256; $i++) {
            $noiseColor = (int) imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
            imagesetpixel($image, rand(0, $this->width), rand(0, $this->height), $noiseColor);
        }

        // Рисуем текст капчи
        for ($i = 0; $i < strlen($this->captchaText); $i++) {
            $angle = rand(-30, 30);
            $x = ($this->width / 4) * $i + rand(10, 20);
            $y = rand(40, 60);
            $textColor = (int) imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
            imagettftext($image, $this->fontSize, $angle, $x, $y, $textColor, $this->fontPath, $this->captchaText[$i]);
        }

        // Вызываем метод для отображения капчи
        $this->renderCaptcha($image);
        imagedestroy($image);
    }

    private function renderCaptcha(mixed $image): void
    {
        header('Content-Type: image/png');
        imagepng($image);
    }

    public function getCaptchaText(): string
    {
        return $_SESSION['captcha_text'];
    }

    public function clearCaptchaText(): void
    {
        unset($_SESSION['captcha_text']);
    }
}
