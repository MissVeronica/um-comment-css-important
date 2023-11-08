<?php
/**
 * Plugin Name:     Ultimate Member - Comment CSS Important
 * Description:     Extension to Ultimate Member for commenting !important from UM asset files except PHP script files.
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Comment_CSS_Important {

    public $directories = array( 'assets/css/', 
                                 'assets/css/pickadate/', 
                                 'assets/dynamic_css/', 
                                 'assets/sass/',
                                 'assets/libs/raty/',
                                 'includes/admin/assets/css2/',
                                 'includes/admin/assets/css/',
                             );

    function __construct() {

        if ( is_admin() && ! defined( 'DOING_AJAX' )) {

            add_filter( 'um_settings_custom_subtabs',                     array( $this, 'um_settings_custom_tabs_comment_important' ), 10, 1 );
            add_filter( 'um_settings_structure',                          array( $this, 'um_settings_structure_comment_important' ), 10, 1 );
            add_filter( 'um_settings_section_comment_important__content', array( $this, 'contents_comment_important_tab' ), 10, 2 );
        }
    }

    public function um_settings_custom_tabs_comment_important( $array ) {

        $array[] = 'comment_important';
        return $array;
    }

    public function contents_comment_important_tab( $html, $section_fields ) {

        if ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'comment_important' ) {

            ob_start();
?>          <table>
            <tr>
                <th style="text-align:left;"><?php _e( 'Folder', 'ultimate-member' ); ?></th>
                <th style="text-align:left;"><?php _e( 'File', 'ultimate-member' ); ?></th>
                <th><?php _e( 'important', 'ultimate-member' ); ?></th>
                <th><?php _e( 'commented', 'ultimate-member' ); ?></th>
            </tr>
<?php       $counts = array( 'important' => 0, 'commented' => 0 );
            foreach( $this->directories as $directory ) {

                echo '<tr><td>' . esc_attr( $directory ) . '</td></tr>';
                $files_status = $this->get_directory_files( $directory );

                if ( $files_status && is_array( $files_status )) {

                    foreach( $files_status as $file => $status ) { ?>
                        <tr>
                            <td></td><td><?php echo esc_attr( str_replace( UM_PATH . $directory, '', $file )); ?></td>
                            <td style="text-align:center;"><?php echo esc_attr( $status['important'] ); ?></td>
                            <td style="text-align:center;"><?php echo esc_attr( $status['commented'] ); ?></td>
                        </tr>
<?php               $counts['important'] = $counts['important'] + $status['important'];
                    $counts['commented'] = $counts['commented'] + $status['commented'];
                    }

                } else { ?>

                    <tr><td></td><td><?php _e( 'No files', 'ultimate-member' ); ?></td></tr>
<?php           }
            } ?>
            <tr>
                <td></td>
                <td></td>
                <td style="text-align:center;"><?php echo esc_attr( $counts['important'] ); ?></td>
                <td style="text-align:center;"><?php echo esc_attr( $counts['commented'] ); ?></td>
            </tr>
            </table>
<?php   }

        return  ob_get_clean() . $html;
    }

    public function get_directory_files( $directory ) {

        $files = glob( UM_PATH . $directory . '*' );

        if ( is_array( $files ) && ! empty( $files )) {

            $files = array_flip( $files );
            $css_files = array();

            foreach( $files as $file => $value ) {
                if ( ! is_dir( $file )) {

                    $content = file_get_contents( $file );

                    if ( in_array( substr( str_replace( UM_PATH . $directory, '', $file ), 0, 3 ), array( 'um-', 'um.' )) ||
                                   $directory == 'assets/dynamic_css/' ) {

                        $content2 = $content;
                        switch( UM()->options()->get( 'um_comment_important_action' )) {

                            case 'comment': $content = str_replace( '!important', "/*!-important*/", $content2 );
                                            break;

                            case 'restore': $content = str_replace( "/*!-important*/", '!important', $content2 );
                                            break;

                            case 'none':
                            default:        break;
                        }

                        if ( $content != $content2 ) {
                            file_put_contents( $file, $content );
                        }
                    }

                    $css_files[$file] = array( 'important' => substr_count( $content, '!important' ),
                                               'commented' => substr_count( $content, "/*!-important*/" ));
                }
            }

            return $css_files;
        }

        return false;
    }

    public function um_settings_structure_comment_important( $settings_structure ) {

        $settings_structure['comment_important'] = array( 
                            'title'  => __( 'CSS !important', 'ultimate-member' ),
                            'sections' => array(
                                '' => array(
                                        'title'  => __( 'Status of "!important" in asset files', 'ultimate-member' ),
                                        'fields' => array(
                                                        array(
                                                            'id'      => 'um_comment_important_action',
                                                            'type'    => 'select',
                                                            'options' => array( 'none'    => __( 'No Action', 'ultimate-member' ),
                                                                                'comment' => __( 'Comment !important', 'ultimate-member' ),
                                                                                'restore' => __( 'Restore !important', 'ultimate-member' ),
                                                                                ),
                                                            'size'    => 'small',
                                                            'label'   => __( 'Comment or Restore "!important" property in UM asset files', 'ultimate-member' ),
                                                            'tooltip' => __( 'Select action or no action for UM asset files.', 'ultimate-member' )
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        );

        return $settings_structure;
    }


}

new UM_Comment_CSS_Important();
