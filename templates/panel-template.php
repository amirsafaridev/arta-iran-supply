<?php
/**
 * Panel Template
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Get settings
$settings = Arta_Iran_Supply_Settings::get_settings();
$panel_title = isset($settings['panel_title']) ? $settings['panel_title'] : 'پنل مدیریت';
$panel_logo = isset($settings['panel_logo']) ? $settings['panel_logo'] : 0;
$login_title = isset($settings['login_title']) ? $settings['login_title'] : 'خوش آمدید';
$login_subtitle = isset($settings['login_subtitle']) ? $settings['login_subtitle'] : 'لطفاً اطلاعات خود را وارد کنید';

$logo_url = $panel_logo ? wp_get_attachment_image_url($panel_logo, 'full') : '';

// Get latest blog posts for news section
$news_posts = get_posts(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'numberposts' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
));

// Helper function to get time ago in Persian
function arta_time_ago_persian($date) {
    $time = time() - strtotime($date);
    
    if ($time < 60) {
        return 'چند لحظه پیش';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' دقیقه پیش';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' ساعت پیش';
    } elseif ($time < 604800) {
        $days = floor($time / 86400);
        return $days . ' روز پیش';
    } elseif ($time < 2592000) {
        $weeks = floor($time / 604800);
        return $weeks . ' هفته پیش';
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return $months . ' ماه پیش';
    } else {
        $years = floor($time / 31536000);
        return $years . ' سال پیش';
    }
}

// Helper function to format date in Persian
function arta_format_date_persian($date) {
    $timestamp = strtotime($date);
    $persian_months = array(
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    );
    
    // Convert to Jalali (simple conversion - for better results use a library)
    $jdate = date('Y/m/d', $timestamp);
    
    return $jdate;
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>پنل مدیریت قراردادها</title>
  <style>@view-transition { navigation: auto; }</style>
  <script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
  <?php wp_head(); ?>
 </head>
 <body><!-- Login Page -->
  <div class="login-page" id="login-page" style="<?php echo $is_logged_in ? 'display: none;' : 'display: flex;'; ?>">
   <div class="login-background">
    <div class="login-shapes">
     <div class="shape shape-1"></div>
     <div class="shape shape-2"></div>
     <div class="shape shape-3"></div>
    </div>
   </div>
   <div class="login-container">
    <div class="login-card">
     <div class="login-header">
      <div class="login-icon-wrapper">
       <?php if ($logo_url) : ?>
        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="login-logo" />
       <?php else : ?>
        <svg class="login-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
         <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
         <circle cx="12" cy="7" r="4"></circle>
        </svg>
       <?php endif; ?>
      </div>
      <h1 class="login-title"><?php echo esc_html($login_title); ?></h1>
      <p class="login-subtitle"><?php echo esc_html($login_subtitle); ?></p>
     </div>
     <form class="login-form" id="login-form">
      <div class="form-group">
       <div class="input-container">
        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
         <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
         <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <input type="text" id="username" name="username" placeholder="نام کاربری" autocomplete="username" required>
       </div>
       <div class="error-message" id="username-error" style="display: none;"></div>
      </div>
      <div class="form-group">
       <div class="input-container">
        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
         <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
         <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        <input type="password" id="password" name="password" placeholder="رمز عبور" autocomplete="current-password" required>
        <button type="button" class="toggle-password" id="toggle-password" tabindex="-1">
         <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
         </svg>
        </button>
       </div>
       <div class="error-message" id="password-error" style="display: none;"></div>
      </div>
      <div class="error-message" id="general-error" style="display: none;"></div>
      <button type="submit" class="btn-login">
       <span>ورود</span>
       <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="5" y1="12" x2="19" y2="12"></line>
        <polyline points="12 5 19 12 12 19"></polyline>
       </svg>
      </button>
     </form>
    </div>
   </div>
  </div><!-- Dashboard Container (hidden initially) -->
  <div class="dashboard-container" id="dashboard-container" style="<?php echo $is_logged_in ? 'display: block;' : 'display: none;'; ?>"><!-- Sidebar -->
  <aside class="sidebar">
   <div class="sidebar-header">
    <div class="logo-container">
     <div class="logo">
      <?php if ($logo_url) : ?>
       <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; padding: 8px;" />
      <?php else : ?>
       📄
      <?php endif; ?>
     </div>
     <div class="logo-text">
      <h2 id="panel-title"><?php echo esc_html($panel_title); ?></h2>
      <p>قراردادها</p>
     </div>
    </div>
   </div>
   <nav class="sidebar-menu">
    <div class="menu-section">
     <div class="menu-section-title">
      منوی اصلی
     </div>
     <div class="menu-item active" data-page="dashboard">
      <span class="menu-item-icon">
       <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
       </svg>
      </span>
      <span>داشبورد</span>
     </div>
     <div class="menu-item" data-page="contracts">
      <span class="menu-item-icon">
       <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
        <line x1="16" y1="13" x2="8" y2="13"></line>
        <line x1="16" y1="17" x2="8" y2="17"></line>
        <polyline points="10 9 9 9 8 9"></polyline>
       </svg>
      </span>
      <span>لیست قراردادها</span>
     </div>
     <div class="menu-item" data-page="settings">
      <span class="menu-item-icon">
       <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
       </svg>
      </span>
      <span>اطلاعات کاربری</span>
     </div>
    </div>
    
   </nav>
   <div class="sidebar-footer">
    <div class="user-profile">
     <div class="user-avatar">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
       <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
       <circle cx="12" cy="7" r="4"></circle>
      </svg>
     </div>
     <div class="user-info">
      <h4><?php echo esc_html($current_user->display_name); ?></h4>
      <p><?php echo esc_html(implode(', ', $current_user->roles)); ?></p>
     </div>
    </div><button class="btn-logout">
     <span class="btn-logout-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
       <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
       <polyline points="16 17 21 12 16 7"></polyline>
       <line x1="21" y1="12" x2="9" y2="12"></line>
      </svg>
     </span>
     <span>خروج از حساب</span>
    </button>
   </div>
  </aside><!-- Main Content -->
  <main class="main-content"><!-- Dashboard Page -->
   <div class="page active" id="dashboard-page">
    <div class="page-header">
     <h1 class="page-title">داشبورد</h1>
     <p class="page-subtitle" id="welcome-message">به پنل مدیریت قراردادها خوش آمدید</p>
    </div><!-- Stats Grid -->
    <div class="stats-grid">
     <div class="stat-card">
      <div class="stat-icon blue">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
        <line x1="16" y1="13" x2="8" y2="13"></line>
        <line x1="16" y1="17" x2="8" y2="17"></line>
        <polyline points="10 9 9 9 8 9"></polyline>
       </svg>
      </div>
      <div class="stat-value">
       5
      </div>
      <div class="stat-label">
       کل قراردادها
      </div>
      <div class="stat-change positive">
       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
       </svg>
       <span>+2 از ماه گذشته</span>
      </div>
     </div>
     <div class="stat-card">
      <div class="stat-icon green">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
       </svg>
      </div>
      <div class="stat-value">
       2
      </div>
      <div class="stat-label">
       قراردادهای تکمیل شده
      </div>
      <div class="stat-change positive">
       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
       </svg>
       <span>+1 از ماه گذشته</span>
      </div>
     </div>
     <div class="stat-card">
      <div class="stat-icon orange">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
       </svg>
      </div>
      <div class="stat-value">
       3
      </div>
      <div class="stat-label">
       در حال انجام
      </div>
      <div class="stat-change positive">
       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
       </svg>
       <span>+1 از ماه گذشته</span>
      </div>
     </div>
     <div class="stat-card">
      <div class="stat-icon purple">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"></line>
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
       </svg>
      </div>
      <div class="stat-value">
       1.35 میلیارد
      </div>
      <div class="stat-label">
       مجموع ارزش قراردادها
      </div>
      <div class="stat-change positive">
       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
       </svg>
       <span>+15% از ماه گذشته</span>
      </div>
     </div>
    </div>
    <!-- News Section -->
    <div class="news-section">
     <div class="section-header">
      <h2 class="section-title"><span>📰</span> <span>آخرین اخبار و اطلاعیه‌ها</span></h2>
     </div>
     <div class="news-list">
      <?php if (!empty($news_posts)) : ?>
       <?php foreach ($news_posts as $post) : setup_postdata($post); ?>
        <div class="news-item">
         <div class="news-date">
          <?php 
          $time_ago = arta_time_ago_persian($post->post_date);
          $formatted_date = arta_format_date_persian($post->post_date);
          echo esc_html($time_ago . ' - ' . $formatted_date);
          ?>
         </div>
         <div class="news-title">
          <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" target="_blank">
           <?php echo esc_html($post->post_title); ?>
          </a>
         </div>
         <div class="news-description">
          <?php 
          $excerpt = $post->post_excerpt;
          if (empty($excerpt)) {
              $excerpt = wp_trim_words($post->post_content, 30, '...');
          }
          echo esc_html($excerpt);
          ?>
         </div>
        </div>
       <?php endforeach; ?>
       <?php wp_reset_postdata(); ?>
      <?php else : ?>
       <div class="news-item">
        <div class="news-title" style="text-align: center; color: #999; padding: 2rem;">
         هیچ خبری یافت نشد
        </div>
       </div>
      <?php endif; ?>
     </div>
    </div><!-- Recent Activity -->
    <div class="activity-section">
     <div class="section-header">
      <h2 class="section-title"><span>⚡</span> <span>فعالیت‌های اخیر</span></h2>
     </div>
     <div class="activity-list" id="recent-activities-list">
      <div class="activity-loading" style="text-align: center; padding: 2rem; color: #999;">
       <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #0066ff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
       <p style="margin-top: 1rem;">در حال بارگذاری فعالیت‌ها...</p>
      </div>
     </div>
    </div>
   </div><!-- Contracts List Page -->
   <div class="page" id="contracts-page">
    <div class="page-header">
     <div class="breadcrumb"><span class="breadcrumb-item" onclick="navigateTo('dashboard')">داشبورد</span> <span class="breadcrumb-separator">←</span> <span class="breadcrumb-item">لیست قراردادها</span>
     </div>
     <h1 class="page-title">لیست قراردادها</h1>
     <p class="page-subtitle">مشاهده و مدیریت تمامی قراردادها</p>
    </div>
    <div id="contracts-container">
     <div class="loading">
      در حال بارگذاری قراردادها...
     </div>
    </div>
   </div><!-- Contract Detail Page -->
   <div class="page" id="contract-detail-page">
    <div id="contract-detail-content"><!-- محتوا به صورت داینامیک اضافه می‌شود -->
    </div>
   </div><!-- Stats Page -->
   <div class="page" id="stats-page">
    <div class="page-header">
     <div class="breadcrumb"><span class="breadcrumb-item" onclick="navigateTo('dashboard')">داشبورد</span> <span class="breadcrumb-separator">←</span> <span class="breadcrumb-item">گزارشات و آمار</span>
     </div>
     <h1 class="page-title">گزارشات و آمار</h1>
     <p class="page-subtitle">نمایش جامع آمار پروژه‌ها</p>
    </div>
    <div class="stats-grid">
     <div class="stat-card">
      <div class="stat-icon blue">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
        <polyline points="17 6 23 6 23 12"></polyline>
       </svg>
      </div>
      <div class="stat-value">
       65%
      </div>
      <div class="stat-label">
       میانگین پیشرفت پروژه‌ها
      </div>
     </div>
     <div class="stat-card">
      <div class="stat-icon green">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
       </svg>
      </div>
      <div class="stat-value">
       4
      </div>
      <div class="stat-label">
       تعداد مشتریان فعال
      </div>
     </div>
     <div class="stat-card">
      <div class="stat-icon orange">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
       </svg>
      </div>
      <div class="stat-value">
       120
      </div>
      <div class="stat-label">
       میانگین روز تکمیل پروژه
      </div>
     </div>
     <div class="stat-card">
      <div class="stat-icon purple">
       <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <circle cx="12" cy="12" r="6"></circle>
        <circle cx="12" cy="12" r="2"></circle>
       </svg>
      </div>
      <div class="stat-value">
       92%
      </div>
      <div class="stat-label">
       نرخ موفقیت تحویل به موقع
      </div>
     </div>
    </div>
   </div><!-- News Page -->
   <div class="page" id="news-page">
    <div class="page-header">
     <div class="breadcrumb"><span class="breadcrumb-item" onclick="navigateTo('dashboard')">داشبورد</span> <span class="breadcrumb-separator">←</span> <span class="breadcrumb-item">اخبار و اطلاعیه‌ها</span>
     </div>
     <h1 class="page-title">اخبار و اطلاعیه‌ها</h1>
     <p class="page-subtitle">آخرین اخبار و به‌روزرسانی‌ها</p>
    </div>
    <div class="news-section">
     <div class="news-list">
      <div class="news-item">
       <div class="news-date">
        3 روز پیش - 1403/02/15
       </div>
       <div class="news-title">
        تمدید قرارداد توسعه اپلیکیشن موبایل
       </div>
       <div class="news-description">
        قرارداد توسعه اپلیکیشن موبایل فروشگاهی با موفقیت تمدید شد و فاز دوم پروژه آغاز گردید. مشتری از نتایج فاز اول رضایت کامل داشت.
       </div>
      </div>
      <div class="news-item">
       <div class="news-date">
        5 روز پیش - 1403/02/13
       </div>
       <div class="news-title">
        تحویل موفق پروژه طراحی سایت شرکتی
       </div>
       <div class="news-description">
        پروژه طراحی سایت شرکت مهندسی سازه پویا با رضایت کامل مشتری تحویل داده شد. سایت با تمامی استانداردهای SEO و بهینه‌سازی پیاده‌سازی شده است.
       </div>
      </div>
      <div class="news-item">
       <div class="news-date">
        1 هفته پیش - 1403/02/08
       </div>
       <div class="news-title">
        امضای قرارداد جدید با شرکت توزیع کالای آسیا
       </div>
       <div class="news-description">
        قرارداد توسعه سیستم مدیریت انبار به ارزش 350 میلیون تومان با این شرکت منعقد شد. این پروژه شامل توسعه نرم‌افزار تحت وب با قابلیت‌های پیشرفته خواهد بود.
       </div>
      </div>
      <div class="news-item">
       <div class="news-date">
        2 هفته پیش - 1403/02/01
       </div>
       <div class="news-title">
        راه‌اندازی موفق اپلیکیشن رزرو آنلاین
       </div>
       <div class="news-description">
        اپلیکیشن رزرو آنلاین برای مجموعه سلامت و زیبایی رویال با موفقیت در استورهای اپلیکیشن منتشر شد و بازخورد مثبت دریافت کرد.
       </div>
      </div>
      <div class="news-item">
       <div class="news-date">
        3 هفته پیش - 1403/01/25
       </div>
       <div class="news-title">
        شروع پروژه پلتفرم آموزش آنلاین
       </div>
       <div class="news-description">
        پروژه ساخت پلتفرم آموزش مجازی برای موسسه آموزش عالی دانش با جلسه کیک‌آف آغاز شد. این پلتفرم شامل امکانات پیشرفته برگزاری کلاس‌های آنلاین خواهد بود.
       </div>
      </div>
     </div>
    </div>
   </div><!-- Settings Page -->
   <div class="page" id="settings-page">
    <div class="page-header">
     <div class="breadcrumb"><span class="breadcrumb-item" onclick="navigateTo('dashboard')">داشبورد</span> <span class="breadcrumb-separator">←</span> <span class="breadcrumb-item">تنظیمات</span>
     </div>
     <h1 class="page-title">اطلاعات کاربری</h1>
     <p class="page-subtitle">مشاهده اطلاعات کاربری</p>
    </div>
    <div class="news-section">
     <div class="section-header">
      <h2 class="section-title"><span>👤</span> <span>اطلاعات کاربری</span></h2>
     </div>
     <div class="activity-list">
      <div class="activity-item">
       <div class="activity-icon info">
        👤
       </div>
       <div class="activity-content">
        <div class="activity-title">
         نام: <?php echo esc_html($current_user->display_name); ?>
        </div>
       </div>
      </div>
      <div class="activity-item">
       <div class="activity-icon info">
        📧
       </div>
       <div class="activity-content">
        <div class="activity-title">
         ایمیل: <?php echo esc_html($current_user->user_email); ?>
        </div>
       </div>
      </div>
      <div class="activity-item">
       <div class="activity-icon info">
        💼
       </div>
       <div class="activity-content">
        <div class="activity-title">
         نقش: <?php echo esc_html(implode(', ', $current_user->roles)); ?>
        </div>
       </div>
      </div>
     </div>
    </div>
   </div>
  </main>
  </div>
  <?php wp_footer(); ?>
  </body>
</html>