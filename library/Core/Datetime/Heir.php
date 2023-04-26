<?php


/**
 * Наследник пхп класса DateTime
 */
class Core_Datetime_Heir extends DateTime {

	public function setTimestamp( $timestamp ) {
		if ( method_exists( 'DateTime', 'setTimestamp' ) ) {
			parent::setTimestamp( $timestamp );
			return;
		}
		$date=getdate( (int) $timestamp );
		$this->setDate( $date['year'] , $date['mon'] , $date['mday'] );
		$this->setTime( $date['hours'] , $date['minutes'] , $date['seconds'] );
	}

	public function getTimestamp() {
		return (method_exists( 'DateTime', 'getTimestamp' )? parent::getTimestamp():$this->format( 'U' ));
	}
}
?>