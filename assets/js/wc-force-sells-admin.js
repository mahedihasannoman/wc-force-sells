jQuery(document).ready(function($){
    'use strict';
    $('.wcfs_add').on( 'click', function( e ){
        console.log(wc_force_sells_admin_i10n.excludeid);
        e.preventDefault();
        var row_count = $('.wcfs_rows tr').length;
        var html = '';
        html += '<tr>';
        html += '<td>';
        html += '<select id="wc_force_sell_ids" class="wc-product-search" style="width: 100% !important;" name="_wcfs_meta['+row_count+'][product]" data-name="product" data-placeholder="'+wc_force_sells_admin_i10n.i18n.search_product+'" data-action="woocommerce_json_search_products_and_variations" data-exclude="'+wc_force_sells_admin_i10n.excludeid+'"></select>';
        html += '</td>';
        html += '<td>';
        html += '<input type="checkbox" name="_wcfs_meta['+row_count+'][removable]" data-name="removable" value="1" />';
        html += '</td>';
        html += '<td>';
        html += '<input type="checkbox" name="_wcfs_meta['+row_count+'][sync_quantity]" data-name="sync_quantity" value="1" />';
        html += '</td>';
        html += '<td>';
        html += '<input type="number" name="_wcfs_meta['+row_count+'][base_quantity]" data-name="base_quantity" value="1" />';
        html += '</td>';
        html += '<td>';
        html += '<input data-repeater-delete type="button" value="Delete" class="button wcfs_delete" />';
        html += '</td>';
        html += '</tr>';
        $(".wcfs_rows").append( html )
        $( document.body ).trigger( 'wc-enhanced-select-init' );
        
    } );

    $(document).on( 'click', '.wcfs_delete' , function( e ){
        e.preventDefault();
        $(this).closest('tr').remove();

        //rearrange fields
        $('.wcfs_rows tr').each(function(count, tabledata){
            var tr = $(this);
            tr.find('td').each (function() {
                var td = $(this);
                if( td.find('select').length>0 && td.find('input').attr('data-name') !='' ) {
                    td.find('select').attr('name', '_wcfs_meta['+count+']['+td.find('select').attr('data-name')+']')
                }
                if( td.find('input').length > 0 && td.find('input').attr('data-name') !='' ) {
                    td.find('input').attr('name', '_wcfs_meta['+count+']['+td.find('input').attr('data-name')+']')
                }
                // do your cool stuff
            }); 
        })

    } )
})