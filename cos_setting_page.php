<?php

function cos_setting_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient privileges!');
    }
    $cos_options = get_option('cos_options');
    if ($cos_options && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce']) && !empty($_POST)) {
        if ($_POST['type'] == 'cos_info_set') {
            foreach ($cos_options as $k => $v) {
                if ($k == 'no_local_file') {
                    $cos_options[$k] = (isset($_POST[$k])) ? True : False;
                } elseif ($k == 'opt') {
                    $cos_options[$k]['auto_rename'] = (isset($_POST['auto_rename'])) ? 1 : 0;
                } else {
                    if ($k != 'cos_url_path') {
                        $cos_options[$k] = (isset($_POST[$k])) ? sanitize_text_field(trim(stripslashes($_POST[$k]))) : '';
                    }
                }
            }
            update_option('cos_options', $cos_options);
            update_option('upload_url_path', esc_url_raw(trim(trim(stripslashes($_POST['upload_url_path'])))));
            ?>
            <div class="update-nag">设置保存完毕!!!</div>
            <?php
        }
    }
    ?>
    <style>
        table {
            border-collapse: collapse;
        }

        table, td, th {
            border: 1px solid #cccccc;
            padding: 5px;
        }

        .buttoncss {
            background-color: #4CAF50;
            border: none;
            cursor: pointer;
            color: white;
            padding: 15px 22px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .buttoncss:hover {
            background-color: #008CBA;
            color: white;
        }

        input {
            border: 1px solid #ccc;
            padding: 5px 0px;
            border-radius: 3px;
            padding-left: 5px;
        }
    </style>
    <div style="margin:5px;">
        <h2>WordPress 腾讯云COS存储设置</h2>
        <hr/>
        <p>WordPress COS，基于腾讯云COS存储与WordPress实现静态资源到COS存储中。提高网站项目的访问速度，以及静态资源的安全存储功能。</p>
        <p>插件网站： <a href="https://www.tintsoft.com" target="_blank">天智软件</a> </p>
        <hr/>
        <form action="<?php echo wp_nonce_url('./admin.php?page=' . COS_BASE_FOLDER . '/cos_actions.php'); ?>"
              name="wpcosform" method="post">
            <table>
                <tr>
                    <td style="text-align:right;">
                        <b>空间名称：</b>
                    </td>
                    <td>
                        <input type="text" name="bucket" value="<?php echo esc_attr($cos_options['bucket']); ?>"
                               size="50"
                               placeholder="BUCKET 比如：laobuluo-xxxxxx"/>

                        <p>1. 需要在腾讯云创建<code>bucket</code>存储桶。注意：填写"存储桶名称-对应ID"。</p>
                        <p>2. 示范： <code>tintsoft-xxxxxx</code></p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>所属地域：</b>
                    </td>
                    <td>
                        <input type="text" name="region" value="<?php echo esc_attr($cos_options['region']); ?>"
                               size="50"
                               placeholder="存储桶 所属地域 比如：ap-shanghai"/>
                        <p>直接填写我们存储桶所属地区，示例：ap-shanghai</p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>访问域名：</b>
                    </td>
                    <td>
                        <input type="text" name="upload_url_path"
                               value="<?php echo esc_url(get_option('upload_url_path')); ?>" size="50"
                               placeholder="请输入COS远程地址"/>

                        <p><b>设置注意事项：</b></p>
                        <p>1. 一般我们是以：<code>https://{cos域名}</code>/<code>自定义文件夹</code>，结尾不要"<code>/</code>"。</p>
                        <p>2. <code>{cos域名}</code> 是需要在设置的存储桶中查看的。"存储桶列表"，当前存储桶的"基础配置"的"访问域名"中。或者自定义的域名。</p>
                        <p>3. 示范1：<code>https://tintsoft-xxxxxx.cos.ap-shanghai.myqcloud.com</code></p>
                        <p>4. 示范2：<code>https://tintsoft-xxxxxx.cos.ap-shanghai.myqcloud.com/wp-content/uploads</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>APPID 设置：</b>
                    </td>
                    <td>
                        <input type="text" name="app_id" value="<?php echo esc_attr($cos_options['app_id']); ?>"
                               size="50"
                               placeholder="APP ID"/>


                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>SecretId 设置：</b>
                    </td>
                    <td><input type="text" name="secret_id" value="<?php echo esc_attr($cos_options['secret_id']); ?>"
                               size="50" placeholder="secretID"/></td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>SecretKey 设置：</b>
                    </td>
                    <td>
                        <input type="text" name="secret_key"
                               value="<?php echo esc_attr($cos_options['secret_key']); ?>" size="50"
                               placeholder="secretKey"/>
                        <p>登入 <a href="https://console.cloud.tencent.com/cam" target="_blank">访问管理</a> 找到你要使用的账户，然后创建或使用现有的 <code>SecretId | SecretKey</code>。如果没有设置的需要创建一组。点击 <code>新建密钥</code></p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>自动重命名：</b>
                    </td>
                    <td>
                        <input type="checkbox"
                               name="auto_rename"
                            <?php
                            if ($cos_options['opt']['auto_rename']) {
                                echo 'checked="TRUE"';
                            }
                            ?>
                        />
                        <p>自动重命名，如果已有安装相关插件和脚本，可不勾选</p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>不在本地保存：</b>
                    </td>
                    <td>
                        <input type="checkbox"
                               name="no_local_file"
                            <?php
                            if ($cos_options['no_local_file']) {
                                echo 'checked="TRUE"';
                            }
                            ?>
                        />
                        <p>如果不想同步在服务器中备份静态文件就 "勾选"。我个人喜欢只存储在腾讯云COS中，这样缓解服务器存储量。</p>
                    </td>
                </tr>

                <tr>
                    <th>

                    </th>
                    <td><input type="submit" name="submit" value="保存设置" class="buttoncss"/></td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <b>注意事项：</b>
                    </td>
                    <td>
                        <p>1. 在测试插件可用之后，已有静态文件可以使用"COSBrowser"等工具迁移【 <a
                                    href="https://cloud.tencent.com/document/product/436/11366" target="_blank">工具官方</a>
                            】 。</p>
                        <p>2. 已有网站迁移静态文件后，内容数据库静态URL地址替换建议使用【wpreplace】批量替换插件。</p>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="type" value="cos_info_set">
        </form>
    </div>
    <?php
}
?>