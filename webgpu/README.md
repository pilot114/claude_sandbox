# WebGPU 3D Game Rendering Examples

Коллекция продвинутых техник 3D рендеринга для игр, реализованных на чистом WebGPU API без использования сторонних библиотек.

## 🚀 Быстрый старт

1. Откройте `index.html` в браузере с поддержкой WebGPU
2. Выберите пример для изучения
3. Изучите код каждого примера - все файлы самодостаточны

## 📋 Требования

- **Браузер:** Chrome 113+, Edge 113+, или Safari Technology Preview
- **GPU:** Любой современный GPU с поддержкой Vulkan/Metal/DirectX 12
- **Знания:** Базовое понимание 3D графики и GLSL/WGSL

## 🎮 Примеры

### 01. Phong Lighting
**Файл:** `01-phong-lighting.html`
**Сложность:** ⭐ Базовый

Классическая модель освещения Phong с тремя компонентами:
- **Ambient** - фоновое освещение
- **Diffuse** - рассеянный свет (зависит от угла к источнику)
- **Specular** - отраженный свет (создает блики)

**Ключевые концепции:**
- Vertex/Fragment шейдеры
- Uniform buffers для передачи матриц
- Vertex buffers с позициями и нормалями
- Depth testing

**Что изучить в коде:**
```javascript
// Структура vertex данных: position (xyz) + normal (xyz)
const vertices = new Float32Array([...]);

// Phong формула в fragment shader
let result = ambient + diffuse + specular;
```

---

### 02. Shadow Mapping
**Файл:** `02-shadow-mapping.html`
**Сложность:** ⭐⭐ Средний

Техника реалистичных теней через двухпроходный рендеринг:

**Pass 1 (Shadow Pass):**
- Рендеринг с точки зрения света
- Запись глубины в depth texture

**Pass 2 (Main Pass):**
- Рендеринг с точки зрения камеры
- Сравнение глубины с shadow map
- PCF (Percentage Closer Filtering) для сглаживания

**Ключевые концепции:**
- Multi-pass rendering
- Depth textures
- Texture sampling с compare mode
- Light space transformations

**Что изучить в коде:**
```javascript
// Создание shadow map текстуры
const shadowDepthTexture = device.createTexture({
    format: 'depth32float',
    usage: RENDER_ATTACHMENT | TEXTURE_BINDING
});

// PCF сэмплирование в шейдере
for (var x = -1; x <= 1; x++) {
    shadow += textureSampleCompare(shadowMap, sampler, coords, depth);
}
```

---

### 03. Normal Mapping
**Файл:** `03-normal-mapping.html`
**Сложность:** ⭐⭐ Средний

Добавление детализации поверхности без дополнительной геометрии:

**Tangent Space:**
- T (tangent) - направление вдоль поверхности
- B (bitangent) - перпендикулярно tangent
- N (normal) - перпендикулярно поверхности
- TBN матрица для трансформации нормалей

**Ключевые концепции:**
- Texture sampling
- Tangent space calculations
- TBN matrix construction
- Per-pixel normal perturbation

**Что изучить в коде:**
```javascript
// Создание процедурной normal map (кирпичи)
function createBrickNormalMap() {
    // RGB = нормаль в tangent space (0.5, 0.5, 1.0 = flat)
}

// TBN трансформация в шейдере
let TBN = mat3x3<f32>(tangent, bitangent, normal);
let worldNormal = TBN * tangentNormal;
```

---

### 04. Skybox
**Файл:** `04-skybox.html`
**Сложность:** ⭐ Базовый

Создание иммерсивного окружения с помощью cube mapping:

**Cube Texture:**
- 6 граней (±X, ±Y, ±Z)
- Sampling по направлению взгляда
- Процедурная генерация градиента неба

**Трюк с глубиной:**
- z = w после vertex shader
- После perspective divide: z = 1.0 (дальняя плоскость)
- Всегда рендерится позади всего

**Ключевые концепции:**
- Cube textures
- Direction vector sampling
- View matrix без трансляции
- Mouse look controls

**Что изучить в коде:**
```javascript
// Процедурная генерация каждой грани
function generateFace(faceIndex) {
    // Конвертация UV в 3D направление для каждой грани
}

// Depth trick в vertex shader
output.position = vec4<f32>(pos.xy, pos.w, pos.w);
```

---

### 05. Particle System
**Файл:** `05-particles.html`
**Сложность:** ⭐⭐⭐ Продвинутый

GPU-ускоренная симуляция частиц через compute shaders:

**Compute Pipeline:**
- 10,000+ частиц обновляются параллельно
- Физика (гравитация, скорость) на GPU
- Storage buffers для чтения/записи

**Double Buffering:**
- Два буфера частиц
- Чтение из одного, запись в другой
- Swap каждый кадр

**Ключевые концепции:**
- Compute shaders
- Storage buffers (read/write)
- Workgroups и parallelism
- Point primitive rendering
- Additive blending

**Что изучить в коде:**
```javascript
// Compute shader для обновления частиц
@compute @workgroup_size(64)
fn main(@builtin(global_invocation_id) id: vec3<u32>) {
    // Обновление позиции, скорости, lifetime
}

// Dispatch с правильным количеством workgroups
computePass.dispatchWorkgroups(Math.ceil(NUM_PARTICLES / 64));
```

---

### 06. Bloom Post-Processing
**Файл:** `06-bloom.html`
**Сложность:** ⭐⭐⭐ Продвинутый

Эффект свечения через многопроходный post-processing:

**Pipeline:**
1. **Scene Pass** → Render to texture (HDR)
2. **Bright Pass** → Извлечение ярких областей
3. **Blur Pass** → Gaussian blur (horizontal + vertical)
4. **Composite Pass** → Комбинирование с оригиналом

**Gaussian Blur:**
- Два прохода (separable filter)
- Horizontal blur → Vertical blur
- Весовые коэффициенты для сглаживания

**Ключевые концепции:**
- Render to texture
- HDR rendering (rgba16float)
- Multi-pass post-processing
- Gaussian blur implementation
- Brightness threshold extraction

**Что изучить в коде:**
```javascript
// Bright pass - извлечение ярких пикселей
let brightness = dot(color.rgb, vec3(0.2126, 0.7152, 0.0722));
if (brightness > threshold) {
    return color * (brightness - threshold);
}

// Separable Gaussian blur
for (var i = 1; i < 5; i++) {
    result += textureSample(texture, uv + offset) * weights[i];
}
```

---

## 🎓 Рекомендуемый порядок изучения

1. **Начинающие:**
   - 01-phong-lighting.html - основы освещения
   - 04-skybox.html - работа с текстурами

2. **Средний уровень:**
   - 03-normal-mapping.html - текстурирование и tangent space
   - 02-shadow-mapping.html - multi-pass rendering

3. **Продвинутые:**
   - 05-particles.html - compute shaders
   - 06-bloom.html - сложный post-processing

## 🔧 Архитектура примеров

Каждый пример следует единой структуре:

```javascript
// 1. Инициализация WebGPU
const adapter = await navigator.gpu.requestAdapter();
const device = await adapter.requestDevice();

// 2. Создание ресурсов
const buffers = createBuffers();
const textures = createTextures();
const pipeline = createPipeline();

// 3. Рендер цикл
function render() {
    updateUniforms();
    recordCommands();
    submit();
    requestAnimationFrame(render);
}
```

## 📚 Ключевые концепции WebGPU

### Buffers
- **Vertex Buffer** - геометрия (позиции, нормали, UV)
- **Index Buffer** - индексы вершин
- **Uniform Buffer** - константы (матрицы, параметры)
- **Storage Buffer** - read/write данные для compute

### Pipelines
- **Render Pipeline** - vertex + fragment шейдеры
- **Compute Pipeline** - compute шейдеры для GPGPU

### Textures
- **Render Targets** - рендеринг в текстуру
- **Depth Textures** - буфер глубины
- **Samplers** - настройки фильтрации и wrapping

### Shaders (WGSL)
- **@vertex** - обработка вершин
- **@fragment** - расчет цвета пикселей
- **@compute** - общие вычисления на GPU

## 🎯 Практические применения

**В играх используются:**

- **Phong/PBR Lighting** - все современные игры
- **Shadow Mapping** - динамические тени (Call of Duty, Battlefield)
- **Normal Mapping** - детализация без затрат на геометрию
- **Skybox** - окружение в открытых мирах
- **Particle Systems** - огонь, дым, магия, взрывы
- **Bloom** - световые эффекты (Unreal Engine, Unity)

## 💡 Советы по оптимизации

1. **Минимизируйте state changes:**
   - Group objects by material/pipeline
   - Batch draw calls

2. **Используйте instancing:**
   - Для множества одинаковых объектов
   - Один draw call вместо тысяч

3. **GPU Frustum Culling:**
   - Compute shader для отсечения невидимых объектов
   - Indirect drawing

4. **Texture Atlases:**
   - Множество текстур в одной
   - Меньше bind операций

5. **Level of Detail (LOD):**
   - Разные версии моделей по расстоянию
   - Автоматическое переключение

## 🔗 Полезные ресурсы

- [WebGPU Specification](https://www.w3.org/TR/webgpu/)
- [WGSL Specification](https://www.w3.org/TR/WGSL/)
- [WebGPU Samples](https://webgpu.github.io/webgpu-samples/)
- [Learn OpenGL](https://learnopengl.com/) - концепции применимы к WebGPU

## 📝 Лицензия

Все примеры предоставлены как есть для образовательных целей.
Свободно используйте, модифицируйте и учитесь!

## 🤝 Вклад

Нашли баг? Хотите добавить пример? Вэлкам!

---

**Happy Rendering! 🎨**
