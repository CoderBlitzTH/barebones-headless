# Barebones Headless WordPress Theme: คู่มือเริ่มต้นใช้งาน

## ภาพรวม

Barebones Headless เป็นธีม WordPress ที่ออกแบบมาเพื่อให้คุณใช้ WordPress เป็น Headless CMS ได้อย่างง่ายดาย โดยทำงานร่วมกับ Next.js หรือ Nuxt.js ได้อย่างลงตัว รองรับฟีเจอร์เจ๋งๆ เช่น GraphQL และระบบ Revalidation (การอัปเดตข้อมูลอัตโนมัติ)

---

## ความต้องการของระบบ

- **WordPress**: เวอร์ชัน 6.7 ขึ้นไป
- **PHP**: เวอร์ชัน 8.0 ขึ้นไป
- **MySQL**: เวอร์ชัน 5.7 ขึ้นไป

### ปลั๊กอินที่ต้องติดตั้ง

1. **WPGraphQL** (จำเป็น)

   - ใช้เพิ่ม GraphQL API เพื่อดึงข้อมูลจาก WordPress
   - ถ้าใช้ ACF ต้องติดตั้งปลั๊กอิน ACF to WPGraphQL เพิ่มด้วย
   - ดาวน์โหลด: [wordpress.org/plugins/wp-graphql/](https://wordpress.org/plugins/wp-graphql/)

2. **Headless Login for WPGraphQL** (จำเป็น)

   - ใช้สำหรับระบบล็อกอินและการยืนยันตัวตน
   - ต้องใช้คู่กับ WPGraphQL
   - ดาวน์โหลด: [github.com/AxeWP/wp-graphql-headless-login/](https://github.com/AxeWP/wp-graphql-headless-login/)

3. **Advanced Custom Fields (ACF)** (แนะนำ)

   - ช่วยสร้างฟิลด์ข้อมูลพิเศษตามที่คุณต้องการ
   - รองรับทั้ง REST API และ GraphQL
   - ดาวน์โหลด: [wordpress.org/plugins/advanced-custom-fields/](https://wordpress.org/plugins/advanced-custom-fields/)

4. **ACF to WPGraphQL** (ถ้าใช้ ACF กับ GraphQL)
   - เชื่อมข้อมูลจาก ACF เข้ากับ GraphQL
   - ดาวน์โหลด: [wordpress.org/plugins/wpgraphql-acf/](https://wordpress.org/plugins/wpgraphql-acf/)

---

## การติดตั้ง

1. ดาวน์โหลดธีมแล้ววางไว้ในโฟลเดอร์ `wp-content/themes/`
2. เข้าไปที่ WordPress Admin แล้วเปิดใช้งานธีม
3. ตั้งค่า Permalink เป็น "Post name" ที่ `Settings > Permalinks`

### การตั้งค่าเริ่มต้น

1. **ตั้งค่าใน WordPress Admin**

   - ไปที่ `Theme Settings` แล้วกรอก:
     - **Frontend URL**: ลิงก์หน้าเว็บของคุณ (เช่น `https://mywebsite.com`)
     - **Blog Base**: ชื่อเส้นทางของบล็อก (เช่น `blog`)
     - **Preview Secret**: รหัสลับสำหรับดูตัวอย่าง
     - **Revalidation Token**: รหัสลับสำหรับอัปเดตข้อมูล

2. **ตั้งค่าผ่าน `wp-config.php` (ถ้าต้องการความปลอดภัยสูง)**
   - เพิ่มโค้ดนี้ในไฟล์ `wp-config.php`:

```php
define('BBH_FRONTEND_URL', 'https://mywebsite.com'); // URL หน้าเว็บ
define('BBH_BLOG_BASE', 'blog'); // เส้นทางบล็อก
define('BBH_PREVIEW_SECRET', 'my-preview-secret'); // รหัสลับสำหรับดูตัวอย่าง
define('BBH_REVALIDATION_SECRET', 'my-revalidation-secret'); // รหัสลับสำหรับอัปเดตข้อมูล
```

3. **ตั้งค่าระบบดูตัวอย่าง (Preview)**
   - ติดตั้งปลั๊กอิน **Headless Login for WPGraphQL**
   - เข้าไปที่ `GraphQL > Settings > Headless Login > Providers > Password`
   - เปิดใช้งาน "Password Provider" แล้วกดบันทึก
   - ไปที่ `GraphQL > GraphiQL IDE` แล้วรันโค้ดนี้ (เปลี่ยน `username` และ `password` เป็นของจริง):

```graphql
mutation {
  login(input: { provider: PASSWORD, credentials: { username: "admin", password: "123456" } }) {
    authToken
    refreshToken
  }
}
```

- คัดลอก `refreshToken` ที่ได้ แล้วเพิ่มในไฟล์ `.env.local` ของ Next.js/Nuxt.js:

```env
NEXTJS_AUTH_REFRESH_TOKEN="your-refresh-token-here"
```

- เสร็จแล้ว! คุณสามารถดูโพสต์แบบร่างได้โดยคลิก "Preview" ใน WordPress Admin

---

## ตัวอย่างการใช้งาน Hooks

### Action

```php
add_action('bbh_after_revalidate', function(array $paths, $response) {
    // แสดงข้อความเมื่ออัปเดตข้อมูลเสร็จ
    if (!is_wp_error($response)) {
        error_log('Revalidation เสร็จแล้วสำหรับ: ' . implode(', ', $paths));
    }
});
```

## Filters

นี่คือ Filters ทั้ง 4 ตัวที่คุณสามารถใช้ปรับแต่งธีมได้ พร้อมตัวอย่างการใช้งานจริง:

### 1. `bbh_frontend_revalidate_url`

- **ใช้ทำอะไร**: เปลี่ยน URL ที่ใช้ส่งคำขอ Revalidation ไปยัง Frontend
- **ตัวอย่างโค้ด**:

```php
add_filter('bbh_frontend_revalidate_url', function() {
    // เปลี่ยนไปใช้ API endpoint อื่นของ Frontend
    return "https://mywebsite.com/custom-api/revalidate";
});
```

- **อธิบาย**: ถ้า Frontend ของคุณใช้ URL อื่นที่ไม่ใช่ค่าเริ่มต้น (เช่น `/api/revalidate`) คุณสามารถปรับที่นี่ได้

---

### 2. `bbh_revalidate_paths`

- **ใช้ทำอะไร**: ปรับเปลี่ยนรายการเส้นทาง (paths) ที่จะส่งไป Revalidate เมื่อโพสต์มีการอัปเดต
- **ตัวอย่างโค้ด**:

```php
add_filter('bbh_revalidate_paths', function(array $paths, WP_Post $post) {
    // เพิ่มเส้นทางพิเศษ เช่น หน้า category ของโพสต์
    $category = get_the_category($post->ID)[0]->slug;
    $paths[] = "/category/" . $category;
    return $paths;
}, 10, 2);
```

- **อธิบาย**: สมมติคุณอยากให้หน้า Category อัปเดตด้วยเมื่อโพสต์มีการเปลี่ยนแปลง โค้ดนี้จะเพิ่มเส้นทางนั้นเข้าไป

---

### 3. `bbh_revalidation_term_paths`

- **ใช้ทำอะไร**: ปรับเปลี่ยนเส้นทาง (paths) ที่เกี่ยวข้องกับ Term (เช่น Category หรือ Tag) ก่อนส่งไป Revalidate
- **ตัวอย่างโค้ด**:

```php
add_filter('bbh_revalidation_term_paths', function(array $paths, WP_Term $term) {
    // เพิ่มหน้าหลักของ taxonomy เข้าไปด้วย
    $paths[] = "/all-" . $term->taxonomy;
    return $paths;
}, 10, 2);
```

- **อธิบาย**: ถ้าคุณมีหน้าแสดงรายการ Term ทั้งหมด (เช่น `/all-categories`) โค้ดนี้จะเพิ่มเข้าไปใน Revalidation

---

### 4. `bbh_allowed_revalidate_domains`

- **ใช้ทำอะไร**: กำหนดโดเมนที่อนุญาตให้ส่งข้อมูล Revalidation เข้ามาที่ WordPress
- **ตัวอย่างโค้ด**:

```php
add_filter('bbh_allowed_revalidate_domains', function(array $domains) {
    // เพิ่มโดเมนพิเศษที่อนุญาต
    $domains[] = "https://staging.mywebsite.com";
    return $domains;
});
```

- **อธิบาย**: ถ้าคุณมีเว็บ Staging หรือโดเมนอื่นที่ต้องการให้เชื่อมต่อได้ เพิ่มเข้าไปในลิสต์นี้

---

## ระบบ Revalidation (การอัปเดตข้อมูล)

### อัปเดตอัตโนมัติ

ระบบจะอัปเดตข้อมูลให้เองเมื่อ:

- บันทึกหรือแก้ไขโพสต์/หน้า
- อัปเดตเมนู
- ลบโพสต์/หน้า

### อัปเดตด้วยตัวเอง

ใช้คำสั่งนี้ใน Terminal:

```bash
curl -X POST https://mywebsite.com/api/revalidate \
  -H "Content-Type: application/json" \
  -H "X-Revalidate-Token: my-revalidation-secret" \
  -d '{"paths": ["/blog/post-1", "/blog/post-2"]}'
```

---

## คำแนะนำเพิ่มเติม

### ประสิทธิภาพ

- ตั้งค่า Revalidation ให้เหมาะกับจำนวนข้อมูล
- ใช้ GraphQL เมื่อต้องการข้อมูลเยอะๆ

### ความปลอดภัย

- เปลี่ยนรหัส Revalidation Token บ่อยๆ
- ใช้ HTTPS เสมอ

### SEO

- เพิ่ม meta tags ในหน้าเว็บ
- ใช้ sitemap แบบอัปเดตอัตโนมัติ

---

## การแก้ปัญหาเบื้องต้น

### ปัญหา Revalidation ไม่ทำงาน

1. เช็คว่า Token ถูกต้อง
2. ตรวจสอบ URL ใน `Theme Settings`
3. ดู log ข้อผิดพลาดในเซิร์ฟเวอร์

### ปัญหา GraphQL

1. ตรวจสอบว่าติดตั้งปลั๊กอินครบ
2. ลองใช้ GraphiQL เพื่อทดสอบ
