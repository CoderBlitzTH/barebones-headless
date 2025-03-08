# Headless WordPress Theme: Barebones Headless Documentation

## Overview

Barebones Headless เป็นธีมที่ออกแบบมาสำหรับใช้ WordPress เป็น Headless CMS โดยเฉพาะ รองรับการทำงานร่วมกับ Next.js และ Nuxt.js พร้อมฟีเจอร์ต่างๆ เช่น GraphQL, และ Revalidation

## ความต้องการของระบบ

- WordPress 6.7 ขึ้นไป
- PHP 8.0 ขึ้นไป
- MySQL 5.7 ขึ้นไป

### ปลั๊กอินที่จำเป็น

1. **WPGraphQL** (บังคับใช้)

   - สำหรับเพิ่ม GraphQL API
   - ต้องติดตั้งร่วมกับ ACF to WPGraphQL ถ้าใช้ ACF
   - [หน้าปลั๊กอิน](https://wordpress.org/plugins/wp-graphql/)

2. **Headless Login for WPGraphQL** (บังคับใช้)

   - สำหรับเพิ่ม Authentication
   - ต้องติดตั้งร่วมกับ WPGraphQL
   - [หน้าปลั๊กอิน](https://github.com/AxeWP/wp-graphql-headless-login/)

3. **Advanced Custom Fields หรือ Advanced Custom Fields Pro** (ถ้าต้องการใช้ แนะนำ)

   - สำหรับสร้าง custom fields
   - รองรับทั้ง REST API และ GraphQL
   - [หน้าปลั๊กอิน](https://wordpress.org/plugins/advanced-custom-fields/)

4. **ACF to WPGraphQL** (ถ้าใช้ทั้ง ACF และ GraphQL)
   - เชื่อมต่อ ACF fields เข้ากับ GraphQL
   - [หน้าปลั๊กอิน](https://wordpress.org/plugins/wpgraphql-acf/)

## การติดตั้ง

1. ดาวน์โหลดธีมและวางในโฟลเดอร์ `wp-content/themes/`
2. เปิดใช้งานธีมใน WordPress Admin
3. ตั้งค่า Permalink เป็น Post name (`Settings > Permalinks`)

### การตั้งค่า

1. ตั้งค่า Frontend URL, Blog Base, Preview Secret, Revalidation Token ใน WordPress Admin (`Theme Settings`)
2. ตั้งค่า environment variable ใน Next.js/Nuxt.js:
3. หากต้องการให้การตั้งค่าเป็นความลับสามารถตั้งค่าได้ที่ `wp-config.php` (ไม่จำเป็นเนื่องจากตั้งค่าได้ที่ Dashboard)

```php
// URL ของ Frontend ของคุณ รวมถึงเครื่องหมายทับที่ไม่ต่อท้าย
define( 'FRONTEND_URL', 'https://frontend-domain.com' );

// Path ของบทความ ตัวอย่าง `https://frontend-domain.com/blog`
define( 'BLOG_BASE', 'blog' );

// รหัสลับหรือโทเคน จะต้องตรงกับตัวแปร .env ใน frontend
define( 'PREVIEW_SECRET', 'preview' );

// รหัสลับหรือโทเคน จะต้องตรงกับตัวแปร .env ใน frontend
define( 'REVALIDATION_SECRET', 'revalidate' );
```

4. การตรวจสอบสิทธิ์สำหรับการดูตัวอย่าง
   หากต้องการค้นหาโพสต์แบบร่างสำหรับ Previews คุณจะต้องทำการตรวจสอบสิทธิ์ด้วย WordPress ขั้นตอนต่อไปนี้เป็นขั้นตอนครั้งเดียว:

- ติดตั้งและเปิดใช้งานปลั๊กอิน [Headless Login for WPGraphQL](https://github.com/AxeWP/wp-graphql-headless-login/)
- ไปที่เมนู GraphQL -> Settings ในหน้า WordPress admin
- ไปที่แท็บ Headless Login -> Providers -> Password -> Password Settings -> Enable Provider -> Save Providers
- ไปที่เมนู GraphQL -> GraphiQL IDE
- คัดลอกข้อความต่อไปนี้แล้ววางลงใน GraphiQL IDE (แทนที่ your_username และ your_password ด้วย User WordPress ของคุณ)

```graphql
mutation login {
  login(input: { provider: PASSWORD, credentials: { username: "your_username", password: "your_password" } }) {
    authToken
    refreshToken
  }
}
```

- คลิกปุ่ม Execute Query หรือคีย์ลัด (Ctrl-Enter) ใน GraphiQL เพื่อรัน mutation
- คัดลอก `refreshToken` ใน mutation ที่ส่งมา
- เปิดไฟล์ Next.js/Nuxt.js `.env.local` และวาง `RefreshToken` ลงในตัวแปร `NEXTJS_AUTH_REFRESH_TOKEN`

```env
# Optional. JWT auth refresh token.
NEXTJS_AUTH_REFRESH_TOKEN="refresh-token-generated-by-grapqh-query"
```

- ตอนนี้คุณควรสามารถดูตัวอย่างโพสต์แบบร่างในแอป Next.js/Nuxt.js ของคุณได้แล้วโดยคลิกปุ่มดูตัวอย่างในผู้ดูแลระบบ WordPress ของคุณ

## Available Hooks

### Actions

1. **bbh_after_revalidate**

   ```php
    add_action( 'bbh_after_revalidate',  function(array $paths, array|\WP_Error $response) {
       // หลังจาก revalidate เสร็จแล้วอยากให้ระบบทำอะไรต่อ
   });
   ```

### Filters

1. **bbh_frontend_revalidate_url**

   ```php
    apply_filters( 'bbh_frontend_revalidate_url',  function(string $path) {
       // แก้ไข revalidate api frontend
   });
   ```

2. **bbh_revalidate_paths**

   ```php
    apply_filters( 'bbh_revalidate_paths',  function(array $paths, WP_Post $post) {
       // จัดการ paths ก่อนส่งไปที่ frontend api
    });
   ```

3. **bbh_revalidation_term_paths**

   ```php
    apply_filters( 'bbh_revalidation_term_paths',  function(array $paths, WP_Term $term) {
       // จัดการ paths ก่อนส่งไปที่ frontend api
    });
   ```

4. **bbh_allowed_revalidate_domains**
   ```php
    apply_filters( 'bbh_allowed_revalidate_domains',  function(array $domain) {
       // อนุญาติในการส่งข้อมูลเข้ามาที่ backend
    });
   ```

## Revalidation System

### Automatic Revalidation

ระบบจะ revalidate อัตโนมัติเมื่อ:

- บันทึก/อัพเดท post หรือ page
- อัพเดทเมนู
- ลบ post หรือ page

### Manual Revalidation

```bash
curl -X POST https://your-frontend/api/revalidate \
  -H "Content-Type: application/json" \
  -H "X-Revalidate-Token: your-token" \
  -d '{"paths":"example"}'
```

## ข้อแนะนำในการใช้งาน

### Performance

1. ตั้งค่า Revalidation ให้เหมาะสม
2. ใช้ GraphQL เมื่อต้องการดึงข้อมูลที่ซับซ้อน

### Security

1. เปลี่ยน Revalidation Token เป็นประจำ
2. ตั้งค่า CORS ให้เหมาะสม
3. ใช้ SSL สำหรับทั้ง WordPress และ Frontend

### SEO

1. ตั้งค่า meta tags ใน Frontend
2. ใช้ dynamic sitemap
3. ตั้งค่า robots.txt ทั้งสองฝั่ง

## การแก้ไขปัญหาเบื้องต้น

### Revalidation Issues

1. ตรวจสอบ token
2. ตรวจสอบ URL endpoints
3. เช็ค error logs

### GraphQL Issues

1. ตรวจสอบการติดตั้ง plugins
2. ตรวจสอบ schema
3. ใช้ GraphiQL debugger

## Support และการอัพเดท

- ติดตามการอัพเดทที่ GitHub repository
- รายงานปัญหาที่ Issue tracker
- ดูตัวอย่างเพิ่มเติมที่ example repository
