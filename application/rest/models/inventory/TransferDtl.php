<?php

namespace rest\models\inventory;

use Yii;
use rest\classes\ActiveRecord;
use rest\models\master\Product;
use rest\models\master\Uom;


/**
 * This is the model class for table "{{%transfer_dtl}}".
 *
 * @property integer $transfer_id
 * @property integer $product_id
 * @property integer $uom_id
 * @property double $qty
 * @property double $total_release
 * @property double $total_receive
 *
 * @property Transfer $transfer
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>  
 * @since 3.0
 */
class TransferDtl extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transfer_dtl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_id', 'uom_id'], 'required'],
            [['transfer_id', 'product_id', 'uom_id'], 'integer'],
            [['qty', 'total_release', 'total_receive'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transfer_id' => 'Transfer ID',
            'product_id' => 'Product ID',
            'uom_id' => 'Uom ID',
            'qty' => 'Qty',
            'total_release' => 'Total Release',
            'total_receive' => 'Total Receive',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer()
    {
        return $this->hasOne(Transfer::className(), ['id' => 'transfer_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
    }

    public function getUom()
    {
        return $this->hasOne(Uom::className(), ['id' => 'uom_id']);
    }

    public function extraFields()
    {
        return[
            'product',
            'uom',
        ];
    }
}