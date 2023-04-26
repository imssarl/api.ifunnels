<?php

class Project_Deliver_Country extends Core_Data_Storage {
    /**
     * Table name
     *
     * @var string
     */
    protected $_table = 'deliver_country';

    /**
     * Filter records by iso
     *
     * @var boolean
     */
    private $_withIsoCodes = false;

    /**
     * Fields of table
     *
     * @var array
     */
    protected $_fields = array( 'id', 'iso', 'name' );

    /**
     * Installing method
     *
     * @return void
     */
    public static function install() {
        Core_Sql::setExec("DROP TABLE IF EXISTS deliver_country");

        Core_Sql::setExec(
            "CREATE TABLE `deliver_country` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `iso` VARCHAR(3) NULL DEFAULT NULL,
                `name` VARCHAR(255) NULL DEFAULT NULL,
                UNIQUE INDEX `id` (`id`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB;"
        );
    }

    /**
     * Setting the passed array to a variable
     *
     * @param [array] $list
     * @return self
     */
    public function withIsoCodes($list) {
        $this->_withIsoCodes = $list;
        return $this;
    }

    protected function assemblyQuery() {
        parent::assemblyQuery();

        if( $this->_withIsoCodes ) {
            $this->_crawler->set_where( 'd.iso IN (' . Core_Sql::fixInjection( $this->_withIsoCodes ) . ')' );
        }

        // $this->_crawler->get_sql($_strSql, $this->_paging);
        // var_dump($_strSql);
    }

    /**
     * Reset variables to default valu
     *
     * @return void
     */
    protected function init() {
        $this->_withIsoCodes = false;
    }
}
