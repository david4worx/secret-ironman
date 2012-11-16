<?php
interface Enrise_SharedInterface
{
    public function random();

    public function shuffle();

    public function reverse();

    public function merge();

    public function search($value);
}