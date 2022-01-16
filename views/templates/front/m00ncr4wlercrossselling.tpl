{foreach from=$crossSellingGroups item=crossSellingGroup name=crossSellingGroups_list}
<section class="page-product-box">
    <h3 class="page-product-heading">
        {$crossSellingGroup.name|escape:'html':'UTF-8'}
    </h3>

    <div class="crossselling_group_block block products_block accessories-block clearfix">
        <div class="block_content">
            <ul id="bxslider_{$crossSellingGroup.id_crossselling_group}" class="bxslider clearfix">
                {foreach from=$crossSellingGroup.accessories item=accessory name=accessories_list}
                    {if ($accessory.allow_oosp || $accessory.quantity_all_versions > 0 || $accessory.quantity > 0) && $accessory.available_for_order && !isset($restricted_country_mode)}
                        {assign var='accessoryLink' value=$link->getProductLink($accessory.id_product, $accessory.link_rewrite, $accessory.category)}
                        <li class="item product-box ajax_block_product{if $smarty.foreach.accessories_list.first} first_item{elseif $smarty.foreach.accessories_list.last} last_item{else} item{/if} product_accessories_description">
                            <div class="product_desc">
                                <a href="{$accessoryLink|escape:'html':'UTF-8'}" title="{$accessory.legend|escape:'html':'UTF-8'}" class="product-image product_image">
                                    <img class="lazyOwl" src="{$link->getImageLink($accessory.link_rewrite, $accessory.id_image, 'home_default')|escape:'html':'UTF-8'}" alt="{$accessory.legend|escape:'html':'UTF-8'}" width="{$homeSize.width}" height="{$homeSize.height}"/>
                                </a>
                                <div class="block_description">
                                    <a href="{$accessoryLink|escape:'html':'UTF-8'}" title="{l s='More' mod='m00ncr4wlercrossselling'}" class="product_description">
                                        {$accessory.description_short|strip_tags|truncate:25:'...'}
                                    </a>
                                </div>
                            </div>
                            <div class="s_title_block">
                                <h5 class="product-name">
                                    <a href="{$accessoryLink|escape:'html':'UTF-8'}">
                                        {$accessory.name|escape:'html':'UTF-8'}
                                    </a>
                                </h5>
                                {if $accessory.show_price && !isset($restricted_country_mode) && !$PS_CATALOG_MODE}
                                    <span class="price product-price">
                                            {if $priceDisplay != 1}
                                                {displayWtPrice p=$accessory.price}{else}{displayWtPrice p=$accessory.price_tax_exc}
                                            {/if}
                                        </span>
                                {/if}
                            </div>
                            <div class="clearfix" style="margin-top:5px">
                                {if !$PS_CATALOG_MODE && ($accessory.allow_oosp || $accessory.quantity > 0)}
                                    <div class="no-print">
                                        <a class="exclusive button ajax_add_to_cart_button" href="{$link->getPageLink('cart', true, NULL, "qty=1&amp;id_product={$accessory.id_product|intval}&amp;token={$static_token}&amp;add")|escape:'html':'UTF-8'}" data-id-product="{$accessory.id_product|intval}" title="{l s='Add to cart' mod='m00ncr4wlercrossselling'}">
                                            <span>{l s='Add to cart' mod='m00ncr4wlercrossselling'}</span>
                                        </a>
                                    </div>
                                {/if}
                            </div>
                        </li>
                    {/if}
                {/foreach}
            </ul>
        </div>
    </div>
</section>
{/foreach}