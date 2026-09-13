# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [4.0.0] - 2026-09-13

### Quebras de Compatibilidade (Breaking Changes)
- **PHP**: Requisito mínimo elevado para PHP `^8.3`.
- **Intervention Image v4**: Atualizado para `intervention/image: ^4.3`. As classes da v2 (`Intervention\Image\Facades\Image` e `Intervention\Image\Constraint`) foram removidas.
- **Novo contrato da closure em `imageUpload()`**:
  - A closure agora recebe uma instância de `Intervention\Image\Interfaces\ImageInterface`.
  - A closure pode retornar:
    - `ImageInterface`: o pacote codifica automaticamente usando a extensão detectada da imagem (`FileExtensionEncoder`);
    - `EncodedImageInterface`: usado diretamente como resultado codificado para gravação.
- **PhpSpreadsheet**: Requisito ajustado para `phpoffice/phpspreadsheet: ^5.0`.
- **Laravel / Illuminate**: Suporte oficial a `illuminate/*: ^11.0|^12.0|^13.0`.

### Adicionado
- Configuração `file-manager.image_driver` em `config/file-manager.php`, permitindo escolher o driver de imagem (padrão: `Intervention\Image\Drivers\Gd\Driver::class`).
- Decodificação especializada com `decodeDataUri()`, `decodeSplFileInfo()` e `decodePath()`.
- Suíte de testes automatizados com `orchestra/testbench` e `phpunit` 11 cobrindo uploads de imagens, closures de transformação, upload de arquivos genéricos e exportação via PhpSpreadsheet 5.

### Modificado
- Normalizada a quebra de linha de todos os arquivos de código-fonte de CR para LF.

---

## Guia de Upgrade (3.x → 4.0)

### 1. Requisitos do Sistema
- **PHP**: `>= 8.3`
- **Laravel**: `^11.0`, `^12.0` ou `^13.0`
- **Intervention Image**: `^4.3`
- **PhpSpreadsheet**: `^5.0`

### 2. Configuração do Driver de Imagem
Uma nova chave foi adicionada em `config/file-manager.php`:
```php
'image_driver' => env('FILE_MANAGER_IMAGE_DRIVER', \Intervention\Image\Drivers\Gd\Driver::class),
```
Se desejar utilizar o driver Imagick, configure a variável de ambiente:
```env
FILE_MANAGER_IMAGE_DRIVER="Intervention\Image\Drivers\Imagick\Driver"
```
Ou publique o arquivo de configuração e ajuste diretamente.

### 3. Migração da Closure em `imageUpload()`
Se você passava uma `\Closure` para `ManagerFile::imageUpload($closure)`:
- **Antes (v3 / Intervention v2)**:
  ```php
  $managerFile->imageUpload(function ($image) {
      return $image->resize(100, 100);
  });
  ```
- **Agora (v4 / Intervention v4)**:
  A closure recebe `Intervention\Image\Interfaces\ImageInterface`.
  ```php
  use Intervention\Image\Interfaces\ImageInterface;

  // Opção A: Retornar ImageInterface (o pacote codifica automaticamente pela extensão do arquivo)
  $managerFile->imageUpload(function (ImageInterface $image) {
      return $image->scale(width: 100);
  });

  // Opção B: Retornar EncodedImageInterface customizado
  use Intervention\Image\Encoders\JpegEncoder;

  $managerFile->imageUpload(function (ImageInterface $image) {
      return $image->encode(new JpegEncoder(quality: 85));
  });
  ```
