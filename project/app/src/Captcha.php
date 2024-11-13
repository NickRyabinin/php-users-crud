<?php

namespace src;

class Captcha
{
    private $width;
    private $height;
    private $fontSize;
    private $fontPath;
    private $captchaText;

    public function __construct($width = 200, $height = 80, $fontSize = 30, $fontPath = '/assets/fonts/OpenSans-Regular.ttf')
    {
        $this->width = $width;
        $this->height = $height;
        $this->fontSize = $fontSize;
        $this->fontPath = $fontPath;
    }

    private function generateRandomText($length = 4)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $this->captchaText = '';
        for ($i = 0; $i < $length; $i++) {
            $this->captchaText .= $characters[rand(0, strlen($characters) - 1)];
        }
        $_SESSION['captcha_text'] = $this->captchaText;
    }

    public function createCaptcha()
    {
        $this->generateRandomText();

        $image = imagecreatetruecolor($this->width, $this->height);
        $backgroundColor = imagecolorallocate($image, 255, 255, 255); // белый фон
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $backgroundColor);

        // Добавляем шумы
        for ($i = 0; $i < 100; $i++) {
            $noiseColor = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
            imagesetpixel($image, rand(0, $this->width), rand(0, $this->height), $noiseColor);
        }

        // Рисуем текст капчи
        for ($i = 0; $i < strlen($this->captchaText); $i++) {
            $angle = rand(-30, 30);
            $x = ($this->width / 4) * $i + rand(10, 20);
            $y = rand(40, 60);
            $textColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
            imagettftext($image, $this->fontSize, $angle, $x, $y, $textColor, $this->fontPath, $this->captchaText[$i]);
        }

        // Вызываем метод для отображения капчи
        $this->renderCaptcha($image);
        imagedestroy($image);
    }

    private function renderCaptcha($image)
    {
        // Устанавливаем заголовок, чтобы браузер знал, что это изображение PNG
        header('Content-Type: image/png');
        
        // Выводим изображение в формате PNG
        imagepng($image);
    }

    public function getCaptchaText()
    {
        return $_SESSION['captcha_text'];
    }

    public function clearCaptchaText()
    {
        unset($_SESSION['captcha_text']);
    }
}
