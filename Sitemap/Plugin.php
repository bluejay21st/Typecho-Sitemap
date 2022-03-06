<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Sitemap.xml 生成器
 * 
 * @package Sitemap
 * @author BlueJay
 * @version 1.0.0
 * @link https://www.cwlog.net/
 *
 */
class Sitemap_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Helper::addRoute('sitemap', '/sitemap.xml', 'Sitemap_Plugin');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('sitemap');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {}
    
    /**
     * 生成sitemap.xml
     */
    public function execute()
    {
        $db = Typecho_Db::get();
        
        //获取配置数据
        $options = Typecho_Widget::widget('Widget_Options');

        //获取页面数据
        $pages = $db->fetchAll($db->select()->from('table.contents')
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', $options->gmtTime)
        ->where('table.contents.type = ?', 'page')
        ->order('table.contents.cid', Typecho_Db::SORT_DESC));

        //获取文章数据
        $contents = $db->fetchAll($db->select()->from('table.contents')
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', $options->gmtTime)
        ->where('table.contents.type = ?', 'post')
        ->order('table.contents.cid', Typecho_Db::SORT_DESC));

        //输出文件头
        header("Content-Type: application/xml");
        echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
        echo '<urlset>'.PHP_EOL;

        //输出页面链接
        foreach($pages as $page) {
            if(Typecho_Router::get($page['type'])){
                $url = Typecho_Common::url(Typecho_Router::url($page['type'], $page), $options->index);
            }else{
                $url = Typecho_Common::url('#', $options->index);
            }

            echo '    <url>'.PHP_EOL;
            echo '        <loc>'.$url.'</loc>'.PHP_EOL;
            echo '        <lastmod>'.date('Y-m-d H:i:s', $page['modified']).'</lastmod>'.PHP_EOL;
            echo '        <changefreq>always</changefreq>'.PHP_EOL;
            echo '        <priority>0.8</priority>'.PHP_EOL;
            echo '    </url>'.PHP_EOL;
        }
        
        //输出文章链接
        foreach($contents as $content) {

            //获取文章分类
            $content['category'] = urlencode(current(Typecho_Common::arrayFlatten($db->fetchAll($db->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $content['cid'])
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)), 'slug')));

            //获取文章缩略名
            $content['slug'] = urlencode($content['slug']);
            
            //格式化文章创建时间
            $created = new Typecho_Date($content['created']);
            $content['year'] = $created->year; $content['month'] = $created->month; $content['day'] = $created->day;

            //生成URL
            $url = Typecho_Common::url(Typecho_Router::url($content['type'], $content), $options->index);

            //输出
            echo '    <url>'.PHP_EOL;
            echo '        <loc>'.$url.'</loc>'.PHP_EOL;
            echo '        <lastmod>'.date('Y-m-d H:i:s', $page['modified']).'</lastmod>'.PHP_EOL;
            echo '        <changefreq>always</changefreq>'.PHP_EOL;
            echo '        <priority>0.5</priority>'.PHP_EOL;
            echo '    </url>'.PHP_EOL;
        }
        echo '</urlset>';
    }

}
