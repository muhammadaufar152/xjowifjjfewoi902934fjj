<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class FPDFAlpha extends Fpdi
{
    protected $extgstates = [];

    public function SetAlpha($alpha, $bm = 'Normal')
    {
        $gs = $this->AddExtGState(['ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm]);
        $this->SetExtGState($gs);
    }

    protected function AddExtGState($parms)
    {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    protected function SetExtGState($gs)
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    function _enddoc()
    {
        if (!empty($this->extgstates) && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }
        parent::_enddoc();
    }

    function _putextgstates()
    {
        foreach ($this->extgstates as $k => $extgstate) {
            $this->_newobj();
            $this->extgstates[$k]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            $parms = $extgstate['parms'];
            $this->_out(sprintf('/ca %.3F', $parms['ca']));
            $this->_out(sprintf('/CA %.3F', $parms['CA']));
            $this->_out('/BM ' . $parms['BM']);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    function _putresourcedict()
    {
        parent::_putresourcedict();
        $this->_out('/ExtGState <<');
        foreach ($this->extgstates as $k => $extgstate) {
            $this->_out('/GS' . $k . ' ' . $extgstate['n'] . ' 0 R');
        }
        $this->_out('>>');
    }

    function _putresources()
    {
        $this->_putextgstates();
        parent::_putresources();
    }
}
