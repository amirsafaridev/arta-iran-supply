<?php
/**
 * Help Menu
 *
 * @package Arta_Iran_Supply
 */

if (!defined('ABSPATH')) {
    exit;
}

class Arta_Iran_Supply_Help_Menu {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_help_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_help_scripts'));
    }
    
    /**
     * Add help submenu under contracts menu
     */
    public function add_help_submenu() {
        add_submenu_page(
            'edit.php?post_type=contract',
            __('راهنما', 'arta-iran-supply'),
            __('راهنما', 'arta-iran-supply'),
            'edit_posts',
            'arta-contracts-help',
            array($this, 'render_help_page')
        );
    }
    
    /**
     * Enqueue help page scripts and styles
     */
    public function enqueue_help_scripts($hook) {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'contract_page_arta-contracts-help') {
            return;
        }
        
        wp_enqueue_style(
            'arta-help-css',
            ARTA_IRAN_SUPPLY_PLUGIN_URL . 'assets/css/help-page.css',
            array(),
            ARTA_IRAN_SUPPLY_VERSION
        );
    }
    
    /**
     * Render help page
     */
    public function render_help_page() {
        ?>
        <div class="wrap arta-help-page">
            <div class="arta-help-header">
                <div class="arta-help-header-content">
                    <div class="arta-help-header-icon-wrapper">
                        <div class="arta-help-header-icon">📚</div>
                    </div>
                    <div class="arta-help-header-text">
                        <h1 class="arta-help-title">
                            <?php _e('راهنمای افزونه مدیریت قراردادها', 'arta-iran-supply'); ?>
                        </h1>
                        <p class="arta-help-subtitle">
                            <?php _e('آموزش کامل استفاده از امکانات افزونه', 'arta-iran-supply'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="arta-help-content">
                <!-- معرفی افزونه -->
                <div class="arta-help-card">
                    <div class="arta-help-card-header">
                        <div class="arta-help-card-icon">ℹ️</div>
                        <h2><?php _e('معرفی افزونه', 'arta-iran-supply'); ?></h2>
                    </div>
                    <div class="arta-help-card-body">
                        <p>
                            <?php _e('افزونه مدیریت قراردادها یک سیستم جامع برای مدیریت قراردادهای سازمانی است که امکان ثبت، پیگیری و مدیریت قراردادها را به صورت کامل فراهم می‌کند.', 'arta-iran-supply'); ?>
                        </p>
                    </div>
                </div>
                
                <!-- امکانات اصلی -->
                <div class="arta-help-card">
                    <div class="arta-help-card-header">
                        <div class="arta-help-card-icon">✨</div>
                        <h2><?php _e('امکانات اصلی', 'arta-iran-supply'); ?></h2>
                    </div>
                    <div class="arta-help-card-body">
                        <div class="arta-feature-grid">
                            <div class="arta-feature-item">
                                <div class="arta-feature-icon">📝</div>
                                <h3><?php _e('مدیریت قراردادها', 'arta-iran-supply'); ?></h3>
                                <p><?php _e('ثبت و مدیریت قراردادها با اطلاعات کامل شامل شماره قرارداد، مشتری، تاریخ شروع و پایان، ارزش قرارداد و پیشرفت پروژه', 'arta-iran-supply'); ?></p>
                            </div>
                            
                            <div class="arta-feature-item">
                                <div class="arta-feature-icon">📊</div>
                                <h3><?php _e('مدیریت مراحل', 'arta-iran-supply'); ?></h3>
                                <p><?php _e('تعریف و مدیریت مراحل مختلف هر قرارداد با امکان تعیین وضعیت (در انتظار، در حال انجام، تکمیل شده)', 'arta-iran-supply'); ?></p>
                            </div>
                            
                            <div class="arta-feature-item">
                                <div class="arta-feature-icon">📎</div>
                                <h3><?php _e('آپلود فایل', 'arta-iran-supply'); ?></h3>
                                <p><?php _e('امکان آپلود فایل‌های مرتبط با هر مرحله از قرارداد شامل تصاویر، اسناد و سایر فایل‌ها', 'arta-iran-supply'); ?></p>
                            </div>
                            
                            <div class="arta-feature-item">
                                <div class="arta-feature-icon">👥</div>
                                <h3><?php _e('نقش‌های کاربری', 'arta-iran-supply'); ?></h3>
                                <p><?php _e('سیستم نقش‌های کاربری با نقش سازمان که امکان دسترسی محدود به قراردادهای خود را دارد', 'arta-iran-supply'); ?></p>
                            </div>
                            
                            <div class="arta-feature-item">
                                <div class="arta-feature-icon">📈</div>
                                <h3><?php _e('پیگیری پیشرفت', 'arta-iran-supply'); ?></h3>
                                <p><?php _e('نمایش پیشرفت پروژه به صورت درصدی و امکان مشاهده وضعیت کلی قراردادها', 'arta-iran-supply'); ?></p>
                            </div>
                            
                            <div class="arta-feature-item">
                                <div class="arta-feature-icon">🎨</div>
                                <h3><?php _e('پنل سازمانی', 'arta-iran-supply'); ?></h3>
                                <p><?php _e('پنل اختصاصی برای سازمان‌ها با رابط کاربری زیبا و کاربرپسند برای مدیریت قراردادها', 'arta-iran-supply'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- نحوه استفاده -->
                <div class="arta-help-card">
                    <div class="arta-help-card-header">
                        <div class="arta-help-card-icon">🚀</div>
                        <h2><?php _e('نحوه استفاده', 'arta-iran-supply'); ?></h2>
                    </div>
                    <div class="arta-help-card-body">
                        <div class="arta-steps">
                            <div class="arta-step">
                                <div class="arta-step-number">1</div>
                                <div class="arta-step-content">
                                    <h3><?php _e('ایجاد قرارداد جدید', 'arta-iran-supply'); ?></h3>
                                    <p><?php _e('از منوی قراردادها، روی "افزودن قرارداد جدید" کلیک کنید و اطلاعات قرارداد را وارد نمایید.', 'arta-iran-supply'); ?></p>
                                </div>
                            </div>
                            
                            <div class="arta-step">
                                <div class="arta-step-number">2</div>
                                <div class="arta-step-content">
                                    <h3><?php _e('تکمیل اطلاعات قرارداد', 'arta-iran-supply'); ?></h3>
                                    <p><?php _e('شماره قرارداد، مشتری، تاریخ شروع و پایان، ارزش قرارداد و درصد پیشرفت را مشخص کنید.', 'arta-iran-supply'); ?></p>
                                </div>
                            </div>
                            
                            <div class="arta-step">
                                <div class="arta-step-number">3</div>
                                <div class="arta-step-content">
                                    <h3><?php _e('تعریف مراحل قرارداد', 'arta-iran-supply'); ?></h3>
                                    <p><?php _e('در بخش "مراحل قرارداد"، مراحل مختلف پروژه را با عنوان، تاریخ، وضعیت و توضیحات اضافه کنید.', 'arta-iran-supply'); ?></p>
                                </div>
                            </div>
                            
                            <div class="arta-step">
                                <div class="arta-step-number">4</div>
                                <div class="arta-step-content">
                                    <h3><?php _e('آپلود فایل‌ها', 'arta-iran-supply'); ?></h3>
                                    <p><?php _e('برای هر مرحله می‌توانید فایل‌های مرتبط را آپلود کنید. روی دکمه "افزودن فایل" کلیک کنید.', 'arta-iran-supply'); ?></p>
                                </div>
                            </div>
                            
                            <div class="arta-step">
                                <div class="arta-step-number">5</div>
                                <div class="arta-step-content">
                                    <h3><?php _e('مدیریت وضعیت', 'arta-iran-supply'); ?></h3>
                                    <p><?php _e('وضعیت قرارداد را می‌توانید در بخش "وضعیت قرارداد" تغییر دهید: در حال انجام، انجام شده یا لغو شده.', 'arta-iran-supply'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- پنل سازمان -->
                <div class="arta-help-card arta-help-card-organization">
                    <div class="arta-help-card-header">
                        <div class="arta-help-card-icon">🏢</div>
                        <h2><?php _e('پنل سازمانی', 'arta-iran-supply'); ?></h2>
                    </div>
                    <div class="arta-help-card-body">
                        <div class="arta-organization-intro">
                            <p>
                                <?php _e('پنل سازمانی یک رابط کاربری اختصاصی و زیبا برای سازمان‌ها است که امکان مدیریت قراردادها را به صورت ساده و کاربرپسند فراهم می‌کند.', 'arta-iran-supply'); ?>
                            </p>
                        </div>
                        
                        <div class="arta-organization-features">
                            <h3><?php _e('امکانات پنل سازمانی:', 'arta-iran-supply'); ?></h3>
                            <div class="arta-org-feature-grid">
                                <div class="arta-org-feature-item">
                                    <div class="arta-org-feature-icon">📊</div>
                                    <h4><?php _e('داشبورد', 'arta-iran-supply'); ?></h4>
                                    <p><?php _e('نمایش آمار کلی قراردادها، پیشرفت پروژه‌ها و آخرین فعالیت‌ها', 'arta-iran-supply'); ?></p>
                                </div>
                                <div class="arta-org-feature-item">
                                    <div class="arta-org-feature-icon">📋</div>
                                    <h4><?php _e('لیست قراردادها', 'arta-iran-supply'); ?></h4>
                                    <p><?php _e('مشاهده و مدیریت تمام قراردادهای سازمان به صورت یکجا', 'arta-iran-supply'); ?></p>
                                </div>
                                <div class="arta-org-feature-item">
                                    <div class="arta-org-feature-icon">🔍</div>
                                    <h4><?php _e('جستجو و فیلتر', 'arta-iran-supply'); ?></h4>
                                    <p><?php _e('جستجوی سریع قراردادها و فیلتر بر اساس وضعیت و تاریخ', 'arta-iran-supply'); ?></p>
                                </div>
                                <div class="arta-org-feature-item">
                                    <div class="arta-org-feature-icon">📄</div>
                                    <h4><?php _e('جزئیات قرارداد', 'arta-iran-supply'); ?></h4>
                                    <p><?php _e('مشاهده جزئیات کامل هر قرارداد شامل مراحل و فایل‌های مرتبط', 'arta-iran-supply'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="arta-organization-steps">
                            <h3><?php _e('نحوه دسترسی به پنل سازمانی:', 'arta-iran-supply'); ?></h3>
                            <div class="arta-org-steps-list">
                                <div class="arta-org-step">
                                    <div class="arta-org-step-number">1</div>
                                    <div class="arta-org-step-content">
                                        <h4><?php _e('ورود به سیستم', 'arta-iran-supply'); ?></h4>
                                        <p><?php _e('با حساب کاربری سازمان خود وارد سایت شوید. اطمینان حاصل کنید که نقش کاربری شما "سازمان" است.', 'arta-iran-supply'); ?></p>
                                    </div>
                                </div>
                                <div class="arta-org-step">
                                    <div class="arta-org-step-number">2</div>
                                    <div class="arta-org-step-content">
                                        <h4><?php _e('دسترسی به پنل', 'arta-iran-supply'); ?></h4>
                                        <p><?php _e('به آدرس <code>/contracts-panel</code> بروید یا از لینک پنل سازمانی در منوی سایت استفاده کنید.', 'arta-iran-supply'); ?></p>
                                    </div>
                                </div>
                                <div class="arta-org-step">
                                    <div class="arta-org-step-number">3</div>
                                    <div class="arta-org-step-content">
                                        <h4><?php _e('مشاهده داشبورد', 'arta-iran-supply'); ?></h4>
                                        <p><?php _e('در صفحه اصلی پنل، آمار کلی قراردادها، پیشرفت پروژه‌ها و آخرین فعالیت‌ها را مشاهده کنید.', 'arta-iran-supply'); ?></p>
                                    </div>
                                </div>
                                <div class="arta-org-step">
                                    <div class="arta-org-step-number">4</div>
                                    <div class="arta-org-step-content">
                                        <h4><?php _e('مدیریت قراردادها', 'arta-iran-supply'); ?></h4>
                                        <p><?php _e('از منوی سمت راست، "لیست قراردادها" را انتخاب کنید تا تمام قراردادهای خود را مشاهده و مدیریت کنید.', 'arta-iran-supply'); ?></p>
                                    </div>
                                </div>
                                <div class="arta-org-step">
                                    <div class="arta-org-step-number">5</div>
                                    <div class="arta-org-step-content">
                                        <h4><?php _e('مشاهده جزئیات', 'arta-iran-supply'); ?></h4>
                                        <p><?php _e('روی هر قرارداد کلیک کنید تا جزئیات کامل، مراحل و فایل‌های مرتبط را مشاهده کنید.', 'arta-iran-supply'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="arta-organization-note">
                            <div class="arta-org-note-icon">💡</div>
                            <div class="arta-org-note-content">
                                <strong><?php _e('نکته مهم:', 'arta-iran-supply'); ?></strong>
                                <p><?php _e('فقط کاربران با نقش "سازمان" می‌توانند به پنل سازمانی دسترسی داشته باشند. در پنل سازمانی، شما فقط می‌توانید قراردادهای خود را مشاهده و مدیریت کنید.', 'arta-iran-supply'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- نکات مهم -->
                <div class="arta-help-card">
                    <div class="arta-help-card-header">
                        <div class="arta-help-card-icon">💡</div>
                        <h2><?php _e('نکات مهم', 'arta-iran-supply'); ?></h2>
                    </div>
                    <div class="arta-help-card-body">
                        <ul class="arta-tips-list">
                            <li>
                                <strong><?php _e('دسترسی محدود:', 'arta-iran-supply'); ?></strong>
                                <?php _e('کاربران با نقش سازمان فقط می‌توانند قراردادهای خود را مشاهده و ویرایش کنند.', 'arta-iran-supply'); ?>
                            </li>
                            <li>
                                <strong><?php _e('مدیریت فایل‌ها:', 'arta-iran-supply'); ?></strong>
                                <?php _e('فایل‌های آپلود شده در هر مرحله قابل مشاهده و دانلود هستند و می‌توانید آن‌ها را حذف کنید.', 'arta-iran-supply'); ?>
                            </li>
                            <li>
                                <strong><?php _e('به‌روزرسانی پیشرفت:', 'arta-iran-supply'); ?></strong>
                                <?php _e('برای نمایش دقیق پیشرفت پروژه، درصد پیشرفت را به صورت منظم به‌روزرسانی کنید.', 'arta-iran-supply'); ?>
                            </li>
                            <li>
                                <strong><?php _e('امنیت:', 'arta-iran-supply'); ?></strong>
                                <?php _e('اطلاعات قراردادها فقط برای کاربران مجاز قابل مشاهده است و دسترسی بر اساس نقش کاربری کنترل می‌شود.', 'arta-iran-supply'); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

