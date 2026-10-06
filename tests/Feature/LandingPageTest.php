<?php

// sākumlapa atveras arī nepieslēgtam apmeklētājam
it('shows the landing page to guests', function () {
    $this->get('/')->assertOk()->assertSee('Rotadata');
});
