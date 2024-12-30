/**
 * fileSizeCheck.js проверяет размер пользовательскго файла,
 * предназначенного к загрузке в поле 'profile_picture' формы.
 */

document.addEventListener('DOMContentLoaded', () => {
  const fileInput = document.getElementById('profile_picture');

  if (fileInput) {
    const form = fileInput.closest('form');
    const errorMessage = document.getElementById('file-error-message');

    form.addEventListener('submit', (event) => {
      const file = fileInput.files[0];
      errorMessage.textContent = '';

      if (file) {
        const maxSizeInBytes = 300 * 1024;
        if (file.size > maxSizeInBytes) {
          event.preventDefault();
          errorMessage.textContent = 'Размер файла не должен превышать 300 кБ. Пожалуйста, выберите другой файл.';
        }
      }
    });
  }
});
