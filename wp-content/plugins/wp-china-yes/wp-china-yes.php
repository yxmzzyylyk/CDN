<?php
/**
 * Plugin Name: WP-China-Yes
 * Description: 将你的WordPress接入本土生态体系中，这将为你提供一个更贴近中国人使用习惯的WordPress
 * Author: WP中国本土化社区
 * Author URI:https://wp-china.org/
 * Version: 3.3.1
 * Network: True
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

(new WP_CHINA_YES)->init();

class WP_CHINA_YES {
    private $page_url;

    public function __construct() {
        $this->page_url = network_admin_url(is_multisite() ? 'settings.php?page=wp-china-yes' : 'options-general.php?page=wp-china-yes');
    }

    public function init() {
        if (is_admin() && ! (defined('DOING_AJAX') && DOING_AJAX)) {
            add_filter(sprintf('%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', plugin_basename(__FILE__)), function ($links) {
                return array_merge(
                    [sprintf('<a href="%s">%s</a>', $this->page_url, '设置')],
                    $links
                );
            });

            if (get_option('wpapi') == 1) {
                add_filter(sprintf('%splugin_action_links', is_multisite() ? 'network_admin_' : ''), function ($links, $plugin = '') {
                    $links[] = '<a target="_blank" href="https://translate.wp-china.org/projects/plugins/' . substr($plugin, 0, strpos($plugin, '/')) . '/zh-cn/default">翻译校准</a>';

                    return $links;
                }, 10, 2);
            }

            update_option("wpapi", get_option('wpapi') ?: '2');
            update_option("super_admin", get_option('super_admin') ?: '2');
            update_option("super_gravatar", get_option('super_gravatar') ?: '1');
            update_option("super_googlefonts", get_option('super_googlefonts') ?: '2');
            update_option("super_googleajax", get_option('super_googleajax') ?: '2');

            register_deactivation_hook(__FILE__, function () {
                delete_option("wpapi");
                delete_option("super_admin");
                delete_option("super_gravatar");
                delete_option("super_googlefonts");
                delete_option("super_googleajax");
            });

            add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', function () {
                add_submenu_page(
                    is_multisite() ? 'settings.php' : 'options-general.php',
                    'WP-China-Yes',
                    'WP-China-Yes',
                    is_multisite() ? 'manage_network_options' : 'manage_options',
                    'wp-china-yes',
                    [$this, 'options_page_html']
                );
            });

            if (get_option('super_admin') != 2 && !stristr($GLOBALS['wp_version'], 'alpha') && !stristr($GLOBALS['wp_version'], 'beta')) {
                $this->page_str_replace('preg_replace', [
                    '~' . home_url('/') . '(wp-admin|wp-includes)/(css|js)/~',
                    sprintf('https://a2.wp-china-yes.net/WordPress@%s/$1/$2/', $GLOBALS['wp_version'])
                ], get_option('super_admin'));
            }
        }

        if (is_admin()) {
            add_action('admin_init', function () {
                register_setting('wpcy', 'wpapi');
                register_setting('wpcy', 'super_admin');
                register_setting('wpcy', 'super_gravatar');
                register_setting('wpcy', 'super_googlefonts');

                add_settings_section(
                    'wpcy_section_main',
                    '将你的WordPress接入本土生态体系中，这将为你提供一个更贴近中国人使用习惯的WordPress',
                    '',
                    'wpcy'
                );

                add_settings_field(
                    'wpcy_field_select_wpapi',
                    '选择应用市场',
                    [$this, 'field_wpapi_cb'],
                    'wpcy',
                    'wpcy_section_main'
                );

                add_settings_field(
                    'wpcy_field_select_super_admin',
                    '加速管理后台',
                    [$this, 'field_super_admin_cb'],
                    'wpcy',
                    'wpcy_section_main'
                );

                add_settings_field(
                    'wpcy_field_select_super_gravatar',
                    '加速G家头像',
                    [$this, 'field_super_gravatar_cb'],
                    'wpcy',
                    'wpcy_section_main'
                );

                add_settings_field(
                    'wpcy_field_select_super_googlefonts',
                    '加速谷歌字体',
                    [$this, 'field_super_googlefonts_cb'],
                    'wpcy',
                    'wpcy_section_main'
                );

                add_settings_field(
                    'wpcy_field_select_super_googleajax',
                    '加速谷歌前端公共库',
                    [$this, 'field_super_googleajax_cb'],
                    'wpcy',
                    'wpcy_section_main'
                );
            });

            add_filter('pre_http_request', function ($preempt, $r, $url) {
                if (( ! stristr($url, 'api.wordpress.org') && ! stristr($url, 'downloads.wordpress.org')) || get_option('wpapi') == 3) {
                    return false;
                }
                if (get_option('wpapi') == 1) {
                    $url = str_replace('api.wordpress.org', 'api.wp-china-yes.net', $url);
                    $url = str_replace('downloads.wordpress.org', 'download.wp-china-yes.net', $url);
                } else {
                    $url = str_replace('api.wordpress.org', 'api.w.org.ibadboy.net', $url);
                    $url = str_replace('downloads.wordpress.org', 'd.w.org.ibadboy.net', $url);
                }

                return wp_remote_request($url, $r);
            }, 10, 3);
        }


        if ( ! (defined('DOING_AJAX') && DOING_AJAX)) {
            if (get_option('super_gravatar') == 1) {
                add_filter('get_avatar', function ($avatar) {
                    return str_replace([
                        'www.gravatar.com',
                        '0.gravatar.com',
                        '1.gravatar.com',
                        '2.gravatar.com',
                        'secure.gravatar.com',
                        'cn.gravatar.com'
                    ], 'gravatar.wp-china-yes.net', $avatar);
                });
            }

            if (get_option('super_googlefonts') != 2) {
                $this->page_str_replace('str_replace', ['fonts.googleapis.com', 'googlefonts.wp-china-yes.net'], get_option('super_googlefonts'));
            }

            if (get_option('super_googleajax') != 2) {
                $this->page_str_replace('str_replace', ['ajax.googleapis.com', 'googleajax.wp-china-yes.net'], get_option('super_googleajax'));
            }
        }
    }

    public function field_wpapi_cb() {
        $wpapi = get_option('wpapi');
        ?>
        <label>
            <input type="radio" value="2" name="wpapi" <?php checked($wpapi, '2'); ?>>官方应用市场加速镜像
        </label>
        <label>
            <input type="radio" value="1" name="wpapi" <?php checked($wpapi, '1'); ?>>本土应用市场（技术试验）
        </label>
        <label>
            <input type="radio" value="3" name="wpapi" <?php checked($wpapi, '3'); ?>>不接管应用市场
        </label>
        <p class="description">
            <b>官方应用市场加速镜像</b>：直接从官方反代并在大陆分发，除了增加对WP-China-Yes插件的更新支持外未做任何更改
        </p>
        <p class="description">
            <b>本土应用市场</b>：与<a href="https://translate.wp-china.org/" target="_blank">本土翻译平台</a>深度整合，为大家提供基于AI翻译+人工辅助校准的全量作品汉化支持<b>（注意，这仍属于试验阶段，存在可能的接口报错、速度缓慢等问题，<a href="https://wp-china.org/forums/forum/228" target="_blank">问题反馈</a>）</b>
        </p>
        <?php
    }

    public function field_super_admin_cb() {
        $this->field_cb('super_admin' , '将WordPress核心所依赖的静态文件切换为公共资源，此选项极大的加快管理后台访问速度', true);
    }

    public function field_super_gravatar_cb() {
        $this->field_cb('super_gravatar' , '为Gravatar头像加速，推荐所有用户启用该选项');
    }

    public function field_super_googlefonts_cb() {
        $this->field_cb('super_googlefonts' , '请只在包含谷歌字体的情况下才启用该选项，以免造成不必要的性能损失');
    }

    public function field_super_googleajax_cb() {
        $this->field_cb('super_googleajax' , '请只在包含谷歌前端公共库的情况下才启用该选项，以免造成不必要的性能损失');
    }

    public function options_page_html() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            update_option("wpapi", sanitize_text_field($_POST['wpapi']));
            update_option("super_admin", sanitize_text_field($_POST['super_admin']));
            update_option("super_gravatar", sanitize_text_field($_POST['super_gravatar']));
            update_option("super_googlefonts", sanitize_text_field($_POST['super_googlefonts']));
            update_option("super_googleajax", sanitize_text_field($_POST['super_googleajax']));

            echo '<div class="notice notice-success settings-error is-dismissible"><p><strong>设置已保存</strong></p></div>';
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        settings_errors('wpcy_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="<?php echo $this->page_url; ?>" method="post">
                <?php
                settings_fields('wpcy');
                do_settings_sections('wpcy');
                submit_button('保存配置');
                ?>
            </form>
        </div>
        <p>
            <a href="https://wp-china.org" target="_blank">WP中国本土化社区</a>的使命是帮助WordPress在中国建立起良好的本土生态环境，以求推进行业整体发展，做大市场蛋糕。<br/>
            特别感谢<a href="https://zmingcx.com/" target="_blank">知更鸟</a>、<a href="https://www.weixiaoduo.com/" target="_blank">薇晓朵团队</a>、<a href="https://www.appnode.com/" target="_blank">AppNode</a>在项目萌芽期给予的帮助。
        </p>
        <?php
    }

    private function field_cb($option_name, $description, $is_global = false) {
        $option_value = get_option($option_name);

        if (!$is_global):
        ?>
        <label>
            <input type="radio" value="3" name="<?php echo $option_name; ?>" <?php checked($option_value, '3'); ?>>前台启用
        </label>
        <label>
            <input type="radio" value="4" name="<?php echo $option_name; ?>" <?php checked($option_value, '4'); ?>>后台启用
        </label>
        <?php endif; ?>
        <label>
            <input type="radio" value="1" name="<?php echo $option_name; ?>" <?php checked($option_value, '1'); ?>><?php echo $is_global ? '启用' : '全局启用' ?>
        </label>
        <label>
            <input type="radio" value="2" name="<?php echo $option_name; ?>" <?php checked($option_value, '2'); ?>>禁用
        </label>
        <p class="description">
            <?php echo $description; ?>
        </p>
        <?php
    }

    private function page_str_replace($replace_func, $param, $level) {
        if ($level == 3 && is_admin()) {
            return;
        } elseif ($level == 4 && !is_admin()) {
            return;
        }

        add_action('init', function () use ($replace_func, $param) {
            ob_start(function ($buffer) use ($replace_func, $param) {
                $param[] = $buffer;
                return call_user_func_array($replace_func, $param);
            });
        });
    }
}
