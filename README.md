# Headless WordPress Theme: Barebones Headless Documentation

## Overview

Barebones Headless เป็นธีมที่ออกแบบมาสำหรับใช้ WordPress เป็น Headless CMS โดยเฉพาะ รองรับการทำงานร่วมกับ Next.js และ Nuxt.js พร้อมฟีเจอร์ต่างๆ เช่น REST API, GraphQL, และ Revalidation

## ความต้องการของระบบ

- WordPress 6.7 ขึ้นไป
- PHP 8.0 ขึ้นไป
- MySQL 5.7 ขึ้นไป

### ปลั๊กอินที่จำเป็น

1. **Advanced Custom Fields หรือ Advanced Custom Fields Pro** (แนะนำ)

   - สำหรับสร้าง custom fields
   - รองรับทั้ง REST API และ GraphQL
   - [หน้าปลั๊กอิน](https://wordpress.org/plugins/advanced-custom-fields/)

2. **WPGraphQL** (ถ้าต้องการใช้ GraphQL)

   - สำหรับเพิ่ม GraphQL API
   - ต้องติดตั้งร่วมกับ ACF to WPGraphQL ถ้าใช้ ACF
   - [หน้าปลั๊กอิน](https://wordpress.org/plugins/wp-graphql/)

3. **WPGraphQL JWT Authentication** (ถ้าต้องการใช้ ระบบสมาชชิก)

   - รองรับ login/logout
   - [หน้าปลั๊กอิน](https://github.com/wp-graphql/wp-graphql-jwt-authentication)

4. **ACF to WPGraphQL** (ถ้าใช้ทั้ง ACF และ GraphQL)
   - เชื่อมต่อ ACF fields เข้ากับ GraphQL
   - [หน้าปลั๊กอิน](https://wordpress.org/plugins/wpgraphql-acf/)

## การติดตั้ง

1. ดาวน์โหลดธีมและวางในโฟลเดอร์ `wp-content/themes/`
2. เปิดใช้งานธีมใน WordPress Admin
3. ตั้งค่า Permalink เป็น Post name (`Settings > Permalinks`)

## Available Hooks

### Filters

1. **bbh_is_disable_frontend**

   ```php
   add_filter('bbh_is_disable_frontend', function() {
    return current_user_can('administrator');
   });
   ```

### Actions

1. **bbh_after_revalidation**

   ```php
   add_action('bbh_after_revalidation', function($path) {
       // ทำงานหลังจาก revalidate
   });
   ```

2. **bbh_before_rest_response**
   ```php
   add_action('bbh_before_rest_response', function($response) {
       // ปรับแต่ง REST response
   });
   ```

## REST API Endpoints

### Posts

```
GET /wp-json/wp/v2/posts
GET /wp-json/wp/v2/posts/{id}
```

### Custom Endpoints

```
POST /wp-json/bbh/v1/revalidate
```

## Revalidation System

### การตั้งค่า

1. ตั้งค่า Frontend URL, Blog Base, Preview Secret, Revalidation Token ใน WordPress Admin (`Headless Settings`)
2. ตั้งค่า environment variable ใน Next.js/Nuxt.js:

```env
REVALIDATE_TOKEN=your-token-from-wordpress
```

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
  -d '{"slug":"example"}'
```

## การแก้ไขที่รองรับ

### 1. REST API Response

```php
// เพิ่ม custom field ใน REST API
add_filter('bbh_rest_response', function($response, $post) {
    $response->data['custom_field'] = get_field('custom_field', $post->ID);
    return $response;
}, 10, 2);
```

### 2. GraphQL Schema

```php
// เพิ่ม custom field ใน GraphQL
add_action('graphql_register_types', function() {
    register_graphql_field('Post', 'customField', [
        'type' => 'String',
        'resolve' => function($post) {
            return get_field('custom_field', $post->ID);
        }
    ]);
});
```

## ข้อแนะนำในการใช้งาน

### Performance

1. ใช้ REST API Cache
2. ตั้งค่า Revalidation ให้เหมาะสม
3. ใช้ GraphQL เมื่อต้องการดึงข้อมูลที่ซับซ้อน

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
