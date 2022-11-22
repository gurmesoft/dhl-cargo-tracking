<?php



/**
 * Dhl order statusu değiştirir
 *
 */
class DHL_Status
{
    public function __construct()
    {
        add_action('init', array( $this, 'initFonksyion' ));
        add_filter('wc_order_statuses', array( $this, 'yeniDurumOlustur' ), 10, 1);
    }
    
    public function initFonksyion()
    {
       
        register_post_status(
            'wc-transit',
            array(
                'label'                     => __('in Transit', 'Eklenti'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop('in Transit <span class="count">(%s)</span>', 'in Transit <span class="count">(%s)</span>'),
            )
        );
        
        register_post_status(
            'wc-delivered',
            array(
                'label'                     => __('Delivered', 'Eklenti'),
                'public'                    => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list'    => true,
                'exclude_from_search'       => false,
                'label_count'               => _n_noop('Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>'),
            )
        );
    }
    public function yeniDurumOlustur($siparisDurumlari)
    {
        $yeniSiparisDurumlari = array();
        foreach ($siparisDurumlari as $anahtar => $durum) {
            $yeniSiparisDurumlari[ $anahtar ] = $durum;
            if ('wc-processing' === $anahtar) {
                $yeniSiparisDurumlari['wc-transit']  = __('In Transit', 'Eklenti');
                $yeniSiparisDurumlari['wc-delivered'] = __('Delivered', 'Eklenti');
            }
        }
        return $yeniSiparisDurumlari;
    }
}
new DHL_Status;
