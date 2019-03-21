<?php namespace Lovata\OrdersShopaholic\Models;

use Event;
use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

use Kharanenka\Scope\ActiveField;
use Kharanenka\Scope\CodeField;

use Lovata\Toolbox\Classes\Helper\PriceHelper;
use Lovata\Toolbox\Traits\Helpers\TraitCached;
use Lovata\Toolbox\Traits\Helpers\PriceHelperTrait;

/**
 * Class ShippingType
 * @package Lovata\OrdersShopaholic\Models
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 *
 * @property                                           $id
 * @property bool                                      $active
 * @property string                                    $code
 * @property string                                    $name
 * @property string                                    $preview_text
 * @property string                                    $price
 * @property float                                     $price_value
 * @property int                                       $sort_order
 * @property \October\Rain\Argon\Argon                 $created_at
 * @property \October\Rain\Argon\Argon                 $updated_at
 *
 * @property \October\Rain\Database\Collection|Order[] $order
 * @method static Order|\October\Rain\Database\Relations\HasMany order()
 *
 * Campaign for Shopaholic
 * @property \October\Rain\Database\Collection|\Lovata\CampaignsShopaholic\Models\Campaign[] $campaign
 * @method static \October\Rain\Database\Relations\BelongsToMany|\Lovata\CampaignsShopaholic\Models\Campaign campaign()
 *
 * Coupons for Shopaholic
 * @property \October\Rain\Database\Collection|\Lovata\CouponsShopaholic\Models\CouponGroup[] $coupon_group
 * @method static \October\Rain\Database\Relations\BelongsToMany|\Lovata\CouponsShopaholic\Models\CouponGroup coupon_group()
 */
class ShippingType extends Model
{
    use ActiveField;
    use Validation;
    use Sortable;
    use CodeField;
    use TraitCached;
    use PriceHelperTrait;

    const EVENT_GET_SHIPPING_PRICE = 'shopaholic.shipping_type.get_price';
    const EVENT_GET_SHIPPING_TYPE_LIST = 'shopaholic.shipping_type.list';

    public $table = 'lovata_orders_shopaholic_shipping_types';

    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
    ];

    public $translatable = ['name', 'preview_text'];

    /** Validation */
    public $rules = [
        'name' => 'required',
        'code' => 'required|unique:lovata_orders_shopaholic_shipping_types',
    ];

    public $attributeNames = [
        'name' => 'lovata.toolbox::lang.field.name',
        'code' => 'lovata.toolbox::lang.field.code',
    ];

    public $jsonable = ['property'];

    public $fillable = [
        'active',
        'code',
        'name',
        'sort_order',
        'price',
        'preview_text',
        'property',
        'method',
    ];

    public $cached = [
        'id',
        'name',
        'code',
        'price_value',
        'preview_text',
        'property',
        'method',
    ];

    public $dates = ['created_at', 'updated_at'];

    public $hasMany = [
        'order' => Order::class,
        'shipping_restriction' => ShippingRestriction::class,
    ];
    public $belongsToMany = [];
    public $belongsTo = [];

    public $arPriceField = ['price'];

    /**
     * Find element by code and return element object
     * @param string $sCode
     * @return $this
     */
    public static function getFirstByCode($sCode)
    {
        if (empty($sCode)) {
            return null;
        }

        $obStatus = self::getByCode($sCode)->first();

        return $obStatus;
    }

    /**
     * Get full price value
     * @return float
     */
    public function getFullPriceValue()
    {
        $fShippingPrice = Event::fire(self::EVENT_GET_SHIPPING_PRICE, [$this], true);
        if ($fShippingPrice !== null) {
            return PriceHelper::round((float) $fShippingPrice);
        }

        $fShippingPrice = $this->price_value;

        return $fShippingPrice;
    }


    /**
     * Get default price
     * @return float
     */
    protected function getDefaultPriceAttribute()
    {
        return floatval($this->attributes['price'] ?? 0);
    }

    /**
     * Get shipping_type options
     * @return array
     */
    public function getShippingTypeOptions()
    {
        $arResult = [];

        $arEventResult = Event::fire(self::EVENT_GET_SHIPPING_TYPE_LIST);

        if (empty($arEventResult)) {
            return $arResult;
        }

        foreach ($arEventResult as $arShippingList) {

            if (empty($arShippingList) || !is_array($arShippingList)) {
                continue;
            }

            $arResult = array_merge($arResult, $arShippingList);
        }

        asort($arResult);

        return $arResult;
    }
}
