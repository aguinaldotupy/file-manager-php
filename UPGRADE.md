# Guia de Upgrade: 3.x para 4.0

## Resumo das Mudanças
A versão 4.0 do `tupy/filemanager` atualiza a integração com a biblioteca de manipulação de imagens para o **Intervention Image ^4.3** e moderniza os requisitos para PHP 8.3+ e Laravel 11/12/13.

---

## 1. Requisitos de Ambiente e Dependências
- **PHP**: `^8.3`
- **Laravel / Illuminate**: `^11.0|^12.0|^13.0`
- **Intervention Image**: `^4.3`
- **PhpSpreadsheet**: `^5.0`

Se o seu projeto possuía dependência explícita travada na Intervention v2 (como `"intervention/image": "^2.7"`), atualize-a para `^4.3` ou remova a restrição caso dependa apenas do `tupy/filemanager`.

---

## 2. Driver de Imagem Configurável
O pacote agora suporta tanto o driver GD quanto o Imagick nativos da Intervention Image 4.
Por padrão, o driver GD (`Intervention\Image\Drivers\Gd\Driver::class`) é utilizado.

Para configurar o driver, você pode definir no arquivo `.env`:
```env
FILE_MANAGER_IMAGE_DRIVER="Intervention\Image\Drivers\Gd\Driver"
# ou para Imagick:
FILE_MANAGER_IMAGE_DRIVER="Intervention\Image\Drivers\Imagick\Driver"
```
Ou ajustar a chave `image_driver` no arquivo `config/file-manager.php`:
```php
'image_driver' => env('FILE_MANAGER_IMAGE_DRIVER', \Intervention\Image\Drivers\Gd\Driver::class),
```

---

## 3. Novo Contrato da Closure em `imageUpload()`
Na Intervention v4, a API fluente e as classes mudaram em relação à v2:
- A classe estática / Facade `Intervention\Image\Facades\Image` não existe mais na v4.
- A classe `Intervention\Image\Constraint` não existe mais na v4.
- Métodos como `resize($w, $h, function ($c) { $c->aspectRatio(); })` foram substituídos por métodos dedicados como `scale(width: 100)` ou `cover()`.

### Novo Contrato
A closure passada para `imageUpload(\Closure $closure)` agora recebe uma instância de `Intervention\Image\Interfaces\ImageInterface` e pode:
1. **Retornar `ImageInterface`**: o pacote codifica automaticamente usando `FileExtensionEncoder` de acordo com a extensão do arquivo;
2. **Retornar `EncodedImageInterface`**: o pacote utilizará o resultado já codificado e o salvará no storage.

### Exemplo de Migração

#### Exemplo 1: Redimensionamento proporcional mantendo aspecto
**Antes (v3):**
```php
ManagerFile::make($model, $file, $options)->imageUpload(function ($image) {
    return $image->resize(1080, null, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
    });
});
```

**Depois (v4):**
```php
use Intervention\Image\Interfaces\ImageInterface;

ManagerFile::make($model, $file, $options)->imageUpload(function (ImageInterface $image) {
    return $image->scaleDown(width: 1080);
});
```

#### Exemplo 2: Codificação customizada com qualidade
**Antes (v3):**
```php
ManagerFile::make($model, $file, $options)->imageUpload(function ($image) {
    return $image->encode('jpg', 80);
});
```

**Depois (v4):**
```php
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Interfaces\ImageInterface;

ManagerFile::make($model, $file, $options)->imageUpload(function (ImageInterface $image) {
    return $image->encode(new JpegEncoder(quality: 80));
});
```

---

## 4. Chamadas Diretas à Intervention Image na Aplicação
Se a sua aplicação fazia chamadas diretas a `Intervention\Image\Facades\Image`:
- Instancie o `ImageManager` explicitamente:
  ```php
  use Intervention\Image\ImageManager;
  use Intervention\Image\Drivers\Gd\Driver;

  $manager = new ImageManager(Driver::class);
  $image = $manager->decode($file);
  ```
- Para redimensionar: use `$image->scale(width: 1080)` ou `$image->scaleDown(width: 1080)`.
- Para codificar: use `$image->encode(new JpegEncoder(quality: 90))` ou métodos equivalentes.
