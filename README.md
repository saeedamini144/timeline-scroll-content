# Timeline Scroll Content

پلاگین وردپرس برای Elementor که یک ویجت سفارشی به نام **Process Timeline** اضافه می‌کند: یک تایم‌لاین عمودی که با اسکرول جلو می‌رود و کنار آن یک پنل چسبان (sticky) که جزئیات مرحلهٔ فعلی را نشان می‌دهد.

- نسخه: 1.0.0
- نیازمندی‌ها: WordPress 6.0+، PHP 7.4+، Elementor 3.20+
- راست‌به‌چپ و چپ‌به‌راست: هر دو بدون استایل جداگانه (با CSS logical properties)
- بدون jQuery و بدون کتابخانهٔ خارجی

---

## فهرست

1. [ویجت چه کاری می‌کند](#۱-ویجت-چه-کاری-میکند)
2. [نحوهٔ عملکرد](#۲-نحوهٔ-عملکرد)
3. [نصب](#۳-نصب)
4. [استفاده در Elementor](#۴-استفاده-در-elementor)
5. [ساختار فایل‌ها](#۵-ساختار-فایلها)
6. [مرجع توابع PHP: فایل اصلی پلاگین](#۶-مرجع-توابع-php-فایل-اصلی-پلاگین)
7. [مرجع توابع PHP: کلاس ویجت](#۷-مرجع-توابع-php-کلاس-ویجت)
8. [مرجع JavaScript](#۸-مرجع-javascript)
9. [معماری CSS](#۹-معماری-css)
10. [فهرست کنترل‌ها](#۱۰-فهرست-کنترلها)
11. [سفارشی‌سازی و توسعه](#۱۱-سفارشیسازی-و-توسعه)
12. [عملکرد، امنیت و دسترسی‌پذیری](#۱۲-عملکرد-امنیت-و-دسترسیپذیری)
13. [عیب‌یابی](#۱۳-عیبیابی)

---

## ۱) ویجت چه کاری می‌کند

ویجت از دو بخش ساخته شده است:

| بخش | محتوا |
|---|---|
| **تایم‌لاین** (ستون دوم) | ریل عمودی، نودهای شماره‌دار، عنوان و متن هر مرحله |
| **پنل جزئیات** (ستون اول، sticky) | زیرعنوان، عنوان، متن و ۱ تا ۳ تصویر برای هر مرحله |

هنگام اسکرول:

1. نوار پیشرفت (progress) روی ریل پر می‌شود.
2. هر نودی که از «خط تریگر» (به‌طور پیش‌فرض وسط صفحه) رد شود فعال می‌شود.
3. پنل چپ با یک انیمیشن fade و slide محتوای مرحلهٔ فعال را نشان می‌دهد.

در تبلت و موبایل پنل چسبان حذف می‌شود و همان جزئیات داخل هر مرحله نمایش داده می‌شود (حالت `stacked`).

---

## ۲) نحوهٔ عملکرد

جریان کار از سه لایه تشکیل شده است:

```
PHP (render)  →  CSS (متغیرها + حالت‌ها)  →  JS (اسکرول و کلاس‌های وضعیت)
```

### لایهٔ PHP

- ویجت کل مراحل را **سمت سرور** رندر می‌کند. بنابراین محتوا برای موتورهای جستجو و حالت بدون JS قابل دیدن است.
- جزئیات هر مرحله **دو بار** در HTML چاپ می‌شود: یک بار داخل پنل sticky و یک بار داخل خود مرحله (`.nd-pt__inline`). CSS تعیین می‌کند کدام یک نمایش داده شود، پس هیچ‌وقت هر دو با هم دیده نمی‌شوند. تصویرهای کپیِ مخفی با `display: none` و `loading="lazy"` دانلود نمی‌شوند.
- تصویر اول فقط وقتی `eager` و `fetchpriority="high"` می‌گیرد که کاربر آن را در گزینهٔ **First image loading** انتخاب کرده باشد (برای ویجتی که بالای صفحه است و تصویر LCP می‌سازد). بقیه همیشه `lazy` هستند.
- تابع `content_template()` همان خروجی `render()` را در ادیتور با JavaScript تولید می‌کند تا پیش‌نمایش زنده دقیقاً یکسان باشد.

### لایهٔ CSS

- مقادیر پیش‌فرض طرح در فایل CSS با **custom property**هایی روی `.nd-pt` تعریف شده‌اند.
- کنترل‌های استایل Elementor با selectorهای `{{WRAPPER}} ...` (اولویت بالاتر) آن‌ها را override می‌کنند. بنابراین CSS فایل در واقع «حالت پیش‌فرض و fallback» است.
- **حالت چیدمان** (`split` یا `stacked`) با یک کنترل responsive انتخاب می‌شود. هر گزینه چند custom property را همزمان تنظیم می‌کند:

  | متغیر | `split` | `stacked` |
  |---|---|---|
  | `--nd-pt-cols` | دو ستون مساوی | یک ستون |
  | `--nd-pt-panel-display` | `grid` | `none` |
  | `--nd-pt-inline-display` | `none` | `block` |

  چون این تنظیم از طریق media queryهای خود Elementor اعمال می‌شود، به breakpointهای سایت شما احترام می‌گذارد و عدد ثابت در CSS نیست. فقط یک fallback برای حداکثر ۱۰۲۴px در فایل CSS هست.

### لایهٔ JavaScript

- یک `Timeline` برای هر ویجت ساخته می‌شود، ولی **فقط یک listener** برای `scroll` و یک listener برای `resize` در کل صفحه وجود دارد.
- در هر فریم (با `requestAnimationFrame`) برای هر نمونه:
  1. ارتفاع نوار پیشرفت محاسبه می‌شود: فاصلهٔ بالای ریل تا خط تریگر.
  2. مرکز هر نود با خط تریگر مقایسه می‌شود و آخرین نودِ رد شده «مرحلهٔ فعلی» می‌شود.
  3. روی مرحله‌ها `is-active` (تا مرحلهٔ فعلی) و روی جزئیات پنل `is-current` (فقط مرحلهٔ فعلی) تنظیم می‌شود.
- ارتفاع ریل با `ResizeObserver` و رویدادهای `resize`، `load` و `document.fonts.ready` دوباره اندازه‌گیری می‌شود. ریل از مرکز نود اول تا مرکز نود آخر کشیده می‌شود.
- در ادیتور Elementor هر تغییر ویجت را از نو رندر می‌کند. یک `MutationObserver` نمونه‌های جدید را پیدا و راه‌اندازی می‌کند و نمونه‌های حذف‌شده از حافظه پاک می‌شوند.

---

## ۳) نصب

1. پوشهٔ `timeline-scroll-content` را zip کنید.
2. در وردپرس: **Plugins → Add New → Upload Plugin** و فایل zip را نصب و فعال کنید. (یا پوشه را مستقیم در `wp-content/plugins/` بگذارید.)
3. اگر Elementor نصب نباشد یا نسخهٔ آن کمتر از ۳.۲۰ باشد، یک اعلان در پیشخوان نمایش داده می‌شود و ویجت ثبت نمی‌شود.

---

## ۴) استفاده در Elementor

1. صفحه را با Elementor باز کنید.
2. ویجت **Process Timeline** را از دستهٔ **ND Widgets** روی صفحه بکشید.
3. در تب **Content**:
   - مراحل را در بخش **Steps** ویرایش کنید (افزودن، حذف و جابه‌جایی با درگ).
   - تگ عنوان‌ها، نمایش یا پنهان بودن هر بخش، اندازهٔ تصویر و رفتار اسکرول را تنظیم کنید.
4. در تب **Style** هر عنصر یک بخش جداگانه دارد.
5. مقدار پیش‌فرض همهٔ کنترل‌ها همان طرح اصلی است و بدون تغییر هم درست دیده می‌شود.

> **نکته:** برای عملکرد sticky، هیچ‌کدام از والدهای ویجت نباید `overflow: hidden` یا `overflow: auto` داشته باشند. جزئیات در بخش [عیب‌یابی](#۱۳-عیبیابی).

---

## ۵) ساختار فایل‌ها

```
timeline-scroll-content/
├── timeline-scroll-content.php            فایل اصلی: هدر، بررسی نسخه‌ها، ثبت دسته‌بندی، ویجت و asset ها
├── widgets/
│   └── class-timeline-scroll-content.php  کلاس ویجت: کنترل‌ها، render، content_template
├── assets/
│   ├── css/timeline-scroll-content.css    استایل ویجت (فقط روی صفحه‌هایی که ویجت دارند بارگذاری می‌شود)
│   └── js/timeline-scroll-content.js      منطق اسکرول (defer، فقط روی صفحه‌هایی که ویجت دارند)
└── README.md
```

CSS و JS با `wp_register_style` و `wp_register_script` فقط **ثبت** می‌شوند. خود ویجت با `get_style_depends()` و `get_script_depends()` آن‌ها را درخواست می‌کند. هر دو با handle با نام `timeline-scroll-content` ثبت شده‌اند.

---

## ۶) مرجع توابع PHP: فایل اصلی پلاگین

فایل: `timeline-scroll-content.php`

### ثابت‌ها

| ثابت | توضیح |
|---|---|
| `ND_WIDGETS_VERSION` | نسخهٔ پلاگین. برای cache-busting فایل‌های CSS و JS هم استفاده می‌شود |
| `ND_WIDGETS_FILE` | مسیر کامل فایل اصلی |
| `ND_WIDGETS_PATH` | مسیر پوشهٔ پلاگین (با `/` در انتها) |
| `ND_WIDGETS_URL` | آدرس پوشهٔ پلاگین |

### کلاس `ND_Widgets_Plugin`

کلاس اصلی پلاگین، به شکل singleton.

| عضو | کار |
|---|---|
| `MIN_ELEMENTOR` | حداقل نسخهٔ Elementor (`3.20.0`) |
| `MIN_PHP` | حداقل نسخهٔ PHP (`7.4`) |
| `CATEGORY` | شناسهٔ دستهٔ ویجت‌ها در پنل Elementor (`nd-widgets`) |
| `instance()` | تنها نمونهٔ کلاس را برمی‌گرداند و در صورت نبودن می‌سازد |
| `__construct()` | private است. فقط `init` را روی `plugins_loaded` ثبت می‌کند |
| `init()` | فایل ترجمه را لود می‌کند، نسخهٔ PHP، فعال بودن Elementor و حداقل نسخهٔ آن را بررسی می‌کند. در صورت ناموفق بودن هر بررسی، اعلان مناسب را نشان می‌دهد و متوقف می‌شود. در غیر این صورت چهار hook Elementor را ثبت می‌کند |
| `register_category( $elements_manager )` | دستهٔ «ND Widgets» را به پنل ویجت‌ها اضافه می‌کند (hook: `elementor/elements/categories_registered`) |
| `register_widgets( $widgets_manager )` | فایل کلاس ویجت را `require_once` می‌کند و ویجت را ثبت می‌کند (hook: `elementor/widgets/register`). کلاس ویجت عمداً همین‌جا لود می‌شود، چون به کلاس‌های Elementor نیاز دارد که باید قبلاً لود شده باشند |
| `register_styles()` | CSS ویجت را فقط ثبت می‌کند، بارگذاری نمی‌کند (hook: `elementor/frontend/after_register_styles`) |
| `register_scripts()` | JS ویجت را با `defer` و در footer ثبت می‌کند (hook: `elementor/frontend/after_register_scripts`). روی WordPress قدیمی‌تر از ۶.۳ آرگومان آرایه‌ای صرفاً به‌عنوان «در footer» تفسیر می‌شود و بدون مشکل کار می‌کند |
| `notice_min_php()` | اعلان خطا: نسخهٔ PHP پایین است |
| `notice_missing_elementor()` | اعلان هشدار: Elementor نصب یا فعال نیست |
| `notice_min_elementor()` | اعلان هشدار: نسخهٔ Elementor پایین‌تر از حداقل است |

اعلان‌ها فقط به کاربرانی نشان داده می‌شوند که `activate_plugins` دارند.

---

## ۷) مرجع توابع PHP: کلاس ویجت

فایل: `widgets/class-timeline-scroll-content.php`
کلاس: `ND_Process_Timeline_Widget` (فرزند `\Elementor\Widget_Base`)

### مشخصات ویجت

| تابع | مقدار برگشتی و توضیح |
|---|---|
| `get_name()` | `nd_process_timeline`. شناسهٔ یکتای ویجت و پایهٔ کلاس `elementor-widget-nd_process_timeline` |
| `get_title()` | عنوان در پنل: Process Timeline |
| `get_icon()` | آیکون `eicon-time-line` |
| `get_categories()` | `['nd-widgets']` |
| `get_keywords()` | کلمات کلیدی جستجو در پنل Elementor |
| `get_style_depends()` | `['timeline-scroll-content']`. CSS فقط وقتی ویجت روی صفحه باشد لود می‌شود |
| `get_script_depends()` | `['timeline-scroll-content']`. JS فقط وقتی ویجت روی صفحه باشد لود می‌شود |
| `has_widget_inner_wrapper()` | `false`. بدون `div.elementor-widget-container` اضافه، DOM سبک‌تر است |
| `is_dynamic_content()` | `false` (محافظ و `protected` مثل ویجت‌های خود Elementor). در این تابع نباید تنظیمات ویجت خوانده شود، چون Elementor آن را روی نمونهٔ «نوع ویجت» هم صدا می‌زند که تنظیماتی ندارد. وجود Dynamic Tag را خود Elementor بررسی می‌کند و در آن صورت از cache خروجی استفاده نمی‌کند |

### توابع کمکی

| تابع | توضیح |
|---|---|
| `size( $size, $unit = 'px' )` | آرایهٔ مقدار برای کنترل `SLIDER` می‌سازد: `['unit' => ..., 'size' => ...]` |
| `dims( $top, $right, $bottom, $left, $unit = 'px' )` | آرایهٔ مقدار برای کنترل `DIMENSIONS` می‌سازد (بدون لینک بودن چهار طرف) |
| `get_default_steps()` | هفت مرحلهٔ طرح اصلی را با متن و تصاویر واقعی به‌عنوان مقدار پیش‌فرض repeater برمی‌گرداند. تصویرها از `picsum.photos` هستند: اولی ۱۲۰۰×۶۷۵ و بقیه ۸۰۰×۵۰۰ |
| `add_text_controls( $name, $selector, $o )` | برای یک عنصر متنی این کنترل‌ها را اضافه می‌کند: تایپوگرافی، رنگ (اختیاری)، سایهٔ متن، تراز responsive و margin responsive. باید داخل یک بخش یا تب باز صدا زده شود. کلید `$o['typography']` مقادیر `fields_options`، کلید `$o['color']` رنگ پیش‌فرض (یا حذف برای نداشتن کنترل رنگ) و کلید `$o['margin']` مقادیر پیش‌فرض desktop، tablet و mobile است |
| `get_tag_options()` | گزینه‌های انتخاب تگ HTML: `h1` تا `h6`، `div`، `span`، `p` |

### ثبت کنترل‌ها

`register_controls()` فقط توابع زیر را به ترتیب صدا می‌زند.

**تب Content**

| تابع | بخش |
|---|---|
| `register_content_steps()` | Steps: repeater مراحل |
| `register_content_timeline()` | Timeline: برچسب دسترسی، تگ عنوان، نمایش متن مرحله |
| `register_content_detail()` | Detail Panel: سوئیچ‌های نمایش، تگ عنوان، اندازهٔ تصویر، نحوهٔ لود، پنهان‌سازی تصاویر کوچک در صفحه‌های کوتاه |
| `register_content_behavior()` | Scroll Behavior: خط تریگر |

**تب Style**

| تابع | بخش |
|---|---|
| `register_style_layout()` | Layout / Box: حالت چیدمان، جای پنل، عرض، فاصله‌ها |
| `register_style_rail()` | Rail & Progress |
| `register_style_node()` | Step Node (تب‌های Normal و Active) |
| `register_style_step_title()` | Step Title (Normal و Active) |
| `register_style_step_text()` | Step Text (Normal و Active) |
| `register_style_detail_box()` | Detail Panel Box |
| `register_style_eyebrow()` | Detail Subtitle |
| `register_style_detail_heading()` | Detail Heading |
| `register_style_detail_body()` | Detail Body |
| `register_style_images()` | Detail Images |
| `register_style_animation()` | Animation |

### رندر

| تابع | توضیح |
|---|---|
| `render()` | خروجی نهایی را چاپ می‌کند. اگر repeater خالی باشد چیزی چاپ نمی‌شود. ساختار: `section.nd-pt` (با `aria-label` اختیاری و `data-nd-trigger`) شامل `.nd-pt__layout` با پنل جزئیات (اگر فعال باشد) و تایم‌لاین (`ol.nd-pt__steps` با یک `li` برای هر مرحله). همهٔ ورودی‌ها escape می‌شوند (`esc_html`، `esc_attr`، `wp_kses_post`) |
| `render_detail( $item, $settings, $index, $context )` | محتوای جزئیات یک مرحله را چاپ می‌کند: زیرعنوان، عنوان، متن و تصاویر. هر مرحله دو بار صدا زده می‌شود: `$context = 'panel'` و `'inline'`. این پارامتر فقط برای یکتا ماندن کلید render attribute تصاویر استفاده می‌شود. اگر دو تصویر داشته باشد کلاس `nd-pt__media--2` اضافه می‌شود و تصویر دوم تمام عرض می‌گیرد |
| `get_image_attributes( $image, $settings, $fallback_alt )` | صفات تگ `<img>` را می‌سازد: `src` (با اندازهٔ انتخاب‌شده در Group_Control_Image_Size)، `width` و `height` برای جلوگیری از layout shift، `srcset` و `sizes` برای اندازه‌های غیر سفارشی، و `alt`. ترتیب انتخاب alt: alt ثبت‌شده در مدیا لایبرری، سپس alt جایگزین آیتم، سپس عنوان جزئیات. اگر تصویر فقط URL باشد (بدون ID)، بدون width و height چاپ می‌شود و جای آن را `aspect-ratio` در CSS حفظ می‌کند |

### پیش‌نمایش زنده

| تابع | توضیح |
|---|---|
| `content_template()` | قالب Underscore/JS که ادیتور برای پیش‌نمایش لحظه‌ای استفاده می‌کند و باید مثل `render()` عمل کند. تابع داخلی `imageUrl()` آدرس تصویر را با `elementor.imagesManager.getImageUrl()` می‌سازد و تابع داخلی `renderDetail()` معادل `render_detail()` است |

---

## ۸) مرجع JavaScript

فایل: `assets/js/timeline-scroll-content.js`
کل کد داخل یک IIFE است و چیزی روی `window` اضافه نمی‌کند.

### Timeline (سازنده)

برای یک المان `.nd-pt` ساخته می‌شود و این‌ها را نگه می‌دارد: `root`، `rail`، `fill`، `steps`، `nodes`، `details` (فقط جزئیات داخل پنل)، `line` (کسر ۰ تا ۱ از `data-nd-trigger`)، `last` (آخرین مرحلهٔ فعال) و `observer`. اگر `ResizeObserver` موجود باشد روی `.nd-pt__steps` گذاشته می‌شود.

| متد | توضیح |
|---|---|
| `Timeline.prototype.measure()` | ارتفاع ریل را برابر فاصلهٔ بالای مرحلهٔ اول تا مرحلهٔ آخر قرار می‌دهد و سپس `update(true)` را صدا می‌زند |
| `Timeline.prototype.update( force )` | ارتفاع نوار پیشرفت را حساب می‌کند، مرحلهٔ فعلی را پیدا می‌کند و کلاس‌ها را تنظیم می‌کند: `is-active` روی مرحله‌های رد شده، `is-current` و `aria-hidden` روی جزئیات پنل. اگر مرحلهٔ فعلی تغییر نکرده باشد و `force` نباشد، کار دیگری انجام نمی‌دهد |
| `Timeline.prototype.destroy()` | `ResizeObserver` را قطع می‌کند |

### توابع سراسری داخل IIFE

| تابع | توضیح |
|---|---|
| `toArray( list )` | تبدیل NodeList به آرایه |
| `each( fn )` | ابتدا نمونه‌هایی را که دیگر داخل DOM نیستند (مثلاً در ادیتور دوباره رندر شده‌اند) حذف و `destroy` می‌کند، سپس `fn` را روی بقیه اجرا می‌کند |
| `onScroll()` | listener اسکرول با throttle از طریق `requestAnimationFrame`. برای همهٔ نمونه‌ها `update(false)` را صدا می‌زند |
| `onResize()` | برای همهٔ نمونه‌ها `measure()` را صدا می‌زند. به رویدادهای `resize`، `load` و `document.fonts.ready` وصل است |
| `initAll()` | همهٔ `.nd-pt` هایی را که هنوز `data-nd-ready` ندارند پیدا و راه‌اندازی می‌کند و در اولین فراخوانی که حداقل یک نمونه وجود دارد listenerهای سراسری را فقط یک‌بار می‌بندد |
| `start()` | `initAll()` را اجرا می‌کند. اگر ادیتور Elementor فعال باشد (`body.elementor-editor-active`) یک `MutationObserver` هم می‌گذارد که با هر تغییر DOM دوباره `initAll()` را (با throttle) صدا بزند |

### ویژگی‌های data

| ویژگی | کاربرد |
|---|---|
| `data-nd-trigger` | درصد خط تریگر (۱۰ تا ۹۰). از کنترل **Trigger line** می‌آید |
| `data-nd-ready` | پس از راه‌اندازی توسط JS اضافه می‌شود تا یک المان دو بار راه‌اندازی نشود |

### کلاس‌های وضعیت که JS اضافه می‌کند

| کلاس | روی چه چیزی | معنی |
|---|---|---|
| `is-active` | `.nd-pt__step` | نود این مرحله از خط تریگر رد شده است |
| `is-current` | `.nd-pt__detail` | جزئیات این مرحله اکنون در پنل دیده می‌شود |

---

## ۹) معماری CSS

فایل: `assets/css/timeline-scroll-content.css`

### نام‌گذاری (BEM)

| کلاس | نقش |
|---|---|
| `.nd-pt` | ریشه (section) |
| `.nd-pt__layout` | گرید دو ستونه |
| `.nd-pt__panel` | پنل sticky |
| `.nd-pt__detail` | جزئیات یک مرحله داخل پنل |
| `.nd-pt__inline` | جزئیات یک مرحله داخل خود مرحله (حالت stacked) |
| `.nd-pt__eyebrow`، `.nd-pt__heading`، `.nd-pt__body` | زیرعنوان، عنوان و متن جزئیات |
| `.nd-pt__media`، `.nd-pt__img`، `.nd-pt__img--main`، `.nd-pt__img--sub` | تصاویر. `--main` تصویر بزرگ و `--sub` تصاویر کوچک |
| `.nd-pt__media--2` | حالتی که مرحله فقط دو تصویر دارد |
| `.nd-pt__timeline`، `.nd-pt__rail`، `.nd-pt__rail-fill` | تایم‌لاین، ریل و نوار پیشرفت |
| `.nd-pt__steps`، `.nd-pt__step`، `.nd-pt__node`، `.nd-pt__content` | فهرست مراحل، هر مرحله، نود و محتوای مرحله |
| `.nd-pt__step-title`، `.nd-pt__text` | عنوان و متن مرحله |
| `.nd-pt--no-detail` | پنل جزئیات خاموش است (تک‌ستونه) |
| `.nd-pt-short-hide-yes` | کلاسی که روی wrapper ویجت قرار می‌گیرد (از کنترل **Hide small images on short screens**) |

### Custom propertyهای مهم

| متغیر | پیش‌فرض | کار |
|---|---|---|
| `--nd-pt-title`، `--nd-pt-text`، `--nd-pt-dim` | `#1D2224`، `#5B605F`، `#8B9296` | رنگ عنوان، متن و متن کم‌رنگ |
| `--nd-pt-accent` | `#FA3000` | رنگ تأکید (نود فعال، نوار پیشرفت، زیرعنوان) |
| `--nd-pt-rail`، `--nd-pt-media-bg` | `rgba(29,34,36,.14)`، `rgba(29,34,36,.06)` | رنگ ریل و پس‌زمینهٔ تصاویر |
| `--nd-pt-node` | `40px` | قطر نود. موقعیت ریل از روی همین مقدار حساب می‌شود، پس با تغییر اندازهٔ نود ریل جابه‌جا نمی‌شود |
| `--nd-pt-rail-w` | `2px` | ضخامت ریل |
| `--nd-pt-speed`، `--nd-pt-detail-speed` | `400ms`، `450ms` | مدت transition مراحل و پنل |
| `--nd-pt-shift` | `14px` | فاصلهٔ حرکت پنل هنگام تغییر |
| `--nd-pt-cols`، `--nd-pt-panel-display`، `--nd-pt-inline-display` | حالت `split` | حالت چیدمان (بخش ۲ را ببینید) |

### نکتهٔ مهم دربارهٔ اولویت (specificity)

کنترل‌های Elementor مقدارهای پیش‌فرض را هم با selector مثل `.elementor-element-xxx .nd-pt__...` خروجی می‌دهند و اولویت‌شان از قواعد فایل CSS بیشتر است. بنابراین برای عوض کردن یک مقدار قابل کنترل، **از کنترل ویجت استفاده کنید**، نه ویرایش فایل CSS. ویرایش فایل CSS برای مواردی است که کنترل ندارند، مثل `display: none` تصاویر کوچک در صفحه‌های کوتاه.

### راست‌به‌چپ

فقط ویژگی‌های logical استفاده شده‌اند: `padding-inline`، `margin-inline`، `inset-inline-start`، `padding-block-end` و مانند آن. بنابراین ریل و چیدمان در RTL به‌طور خودکار جابه‌جا می‌شوند. کنترل **Panel position** هم با `order` کار می‌کند و در RTL جهتش خودکار برعکس می‌شود. فایل `-rtl.css` جدا لازم نیست.

### کاهش حرکت

با `prefers-reduced-motion: reduce` همهٔ transitionها خاموش می‌شوند.

---

## ۱۰) فهرست کنترل‌ها

### تب Content

| بخش | کنترل‌ها |
|---|---|
| **Steps** | Repeater با فیلدهای: عنوان مرحله، متن مرحله، زیرعنوان، عنوان، متن، سه تصویر (Image 1 تا 3) و alt جایگزین. همهٔ فیلدهای متنی و تصویری Dynamic Tag پشتیبانی می‌کنند |
| **Timeline** | Accessible label، Step title HTML tag، Show step text |
| **Detail Panel** | Show detail panel، Show subtitle، Show heading، Show body text، Show images، Heading HTML tag، Image size، First image loading، Hide small images on short screens |
| **Scroll Behavior** | Trigger line |

### تب Style

| بخش | کنترل‌ها |
|---|---|
| **Layout / Box** | Layout، Panel position، Content width، Gap between panel and timeline، Side padding، Panel sticky offset، Space between steps، Space after the last step، Gap between node and content، Inline detail top spacing |
| **Rail & Progress** | Rail width، Rail color، Progress color |
| **Step Node** | Size، Typography، Border radius؛ تب Normal: رنگ متن، بک‌گراند، بوردر، سایه؛ تب Active: رنگ متن، بک‌گراند، رنگ بوردر، سایه |
| **Step Title** | Typography، Text Shadow، Alignment، Margin؛ تب Normal و Active: رنگ و Opacity |
| **Step Text** | مثل Step Title، به‌علاوهٔ Max width |
| **Detail Panel Box** | Background، Padding، Border، Border radius، Box Shadow |
| **Detail Subtitle / Heading / Body** | Typography، Color، Text Shadow، Alignment، Margin (متن جزئیات Max width هم دارد) |
| **Detail Images** | Gap، سه نسبت تصویر، دو سقف ارتفاع در پنل، Object fit، Border radius، Placeholder background، Border، Box Shadow، CSS Filters در Normal و Hover |
| **Animation** | Step transition (ms)، Detail panel transition (ms)، Detail slide distance |

مقدار پیش‌فرض کنترل‌های responsive برای desktop، tablet و mobile طوری تنظیم شده که ظاهر پیش‌فرض با طرح اصلی یکی باشد. برای رنگ‌ها و تایپوگرافی، انتخاب Global Color و Global Font در Elementor فعال است.

---

## ۱۱) سفارشی‌سازی و توسعه

### تغییر محتوای پیش‌فرض مراحل

آرایهٔ `$rows` داخل `get_default_steps()` را ویرایش کنید. هر ردیف: شناسهٔ تصویر (seed)، عنوان مرحله، متن مرحله، زیرعنوان، عنوان جزئیات، متن جزئیات، تعداد تصاویر. این مقدارها فقط برای ویجت‌های **جدید** پیش‌فرض‌اند و ویجت‌هایی که قبلاً ذخیره شده‌اند تغییر نمی‌کنند.

### افزودن یک کنترل جدید

1. کنترل را داخل تابع `register_*` مربوط اضافه کنید.
2. اگر در خروجی تأثیر ساختاری دارد (مثلاً عنصر جدید)، هم `render()` و هم `content_template()` را به‌روز کنید تا پیش‌نمایش با خروجی نهایی یکی بماند.
3. برای استایل، در `selectors` از `{{WRAPPER}}` و کلاس‌های `nd-pt__*` استفاده کنید.

### افزودن ویجت جدید به همین پلاگین

1. فایل `widgets/class-<name>.php` با کلاس جدید (فرزند `Widget_Base`) بسازید.
2. در `register_widgets()` فایل را `require_once` و ویجت را با `$widgets_manager->register( new ... )` ثبت کنید.
3. اگر استایل یا اسکریپت جدا دارد، در `register_styles()` و `register_scripts()` ثبت کنید و در ویجت از `get_style_depends()` و `get_script_depends()` برگردانید.

### تغییر حداقل نسخه‌ها

ثابت‌های `MIN_ELEMENTOR` و `MIN_PHP` در کلاس `ND_Widgets_Plugin` و هدرهای `Requires PHP` و `Requires at least` بالای فایل اصلی.

### ترجمه

همهٔ رشته‌ها با text domain به نام `timeline-scroll-content` ترجمه‌پذیرند. فایل‌های `.po` و `.mo` را در پوشهٔ `languages/` بگذارید.

---

## ۱۲) عملکرد، امنیت و دسترسی‌پذیری

**عملکرد**
- CSS و JS فقط در صفحه‌هایی که ویجت دارند لود می‌شوند. JS با `defer` اجرا می‌شود.
- بدون jQuery و بدون کتابخانهٔ خارجی. یک listener اسکرول برای کل صفحه.
- DOM بدون wrapper اضافه (`has_widget_inner_wrapper()` برابر `false`).
- تصاویر `lazy` و `decoding="async"` با `width` و `height` و `aspect-ratio` برای جلوگیری از layout shift. تصویر اول قابل تنظیم برای LCP.
- پشتیبانی از cache المان Elementor وقتی Dynamic Tag استفاده نشده باشد.

**امنیت**
- همهٔ خروجی‌ها escape می‌شوند: `esc_html`، `esc_attr` و `wp_kses_post` برای متن‌های چندخطی. صفات تگ‌ها (از جمله `src` تصاویر) با `add_render_attribute()` و `print_render_attribute_string()` چاپ می‌شوند که خودشان escape می‌کنند.
- نام تگ‌های HTML با `Utils::validate_html_tag()` اعتبارسنجی می‌شود.
- فقط کاربران با دسترسی `activate_plugins` اعلان‌های ادمین را می‌بینند.

**دسترسی‌پذیری**
- تگ‌های معنایی: `section`، `ol` و `li` برای فهرست مراحل، عنوان‌های قابل انتخاب.
- شمارهٔ نودها `aria-hidden` است، چون شماره‌گذاری از خود `ol` می‌آید.
- پنل جزئیات `aria-live="polite"` دارد و جزئیات مخفی `aria-hidden="true"` می‌گیرند.
- هر تصویر `alt` دارد (با fallback).
- با `prefers-reduced-motion` انیمیشن‌ها خاموش می‌شوند.

---

## ۱۳) عیب‌یابی

| مشکل | علت و راه‌حل |
|---|---|
| پنل چپ نمی‌چسبد (sticky کار نمی‌کند) | یکی از والدهای ویجت `overflow: hidden/auto/scroll` یا `transform` دارد. در تنظیمات کانتینر والد، Overflow را روی Default بگذارید. همچنین Layout در دستگاه فعلی نباید روی Stacked باشد |
| ویجت در پنل Elementor دیده نمی‌شود | Elementor نصب یا فعال نیست، یا نسخهٔ آن کمتر از ۳.۲۰ است. اعلان پیشخوان را بخوانید |
| در تبلت پنل جزئیات ندارم | به‌طور پیش‌فرض در تبلت Layout روی Stacked است و جزئیات داخل هر مرحله نمایش داده می‌شود. برای دو ستونه بودن در تبلت، Layout را در حالت tablet روی «Sticky panel + timeline» بگذارید |
| نوار پیشرفت یا نود فعال نمی‌شود | JS لود نشده است. بررسی کنید `timeline-scroll-content.js` در footer صفحه هست و خطای کنسول ندارد. کش صفحه یا minify/defer پلاگین‌های بهینه‌ساز را هم بررسی کنید |
| فعال شدن مراحل زود یا دیر است | کنترل **Trigger line** را تغییر دهید (درصدی از ارتفاع صفحه) |
| تصاویر نمایش داده نمی‌شوند | اگر URL خارجی (مثل `picsum.photos`) استفاده شده، دسترسی اینترنت لازم است. برای سایت واقعی از مدیا لایبرری استفاده کنید |
| بعد از تغییر ظاهری در ادیتور، تغییر دیده نمی‌شود | همان مقدار از طریق کنترل ویجت تنظیم شده و اولویتش بالاتر از CSS فایل است. تغییر را از کنترل انجام دهید، نه با ویرایش فایل CSS |
| تغییر نام پلاگین یا پوشه باعث غیرفعال شدن شد | وردپرس پلاگین را با نام پوشه می‌شناسد. نسخهٔ قبلی را حذف کنید و نسخهٔ جدید را فعال کنید |
