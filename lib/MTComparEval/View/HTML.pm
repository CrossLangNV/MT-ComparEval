package MTComparEval::View::HTML;

use strict;
use base 'Catalyst::View::TT';

__PACKAGE__->config({
    TEMPLATE_EXTENSION => '.tt2',
    INCLUDE_PATH => [
        MTComparEval->path_to( 'root', 'src' ),
        MTComparEval->path_to( 'root', 'lib' )
    ],
    PRE_PROCESS  => 'config/main',
    WRAPPER      => 'site/wrapper',
    ERROR        => 'error.tt2',
    TIMER        => 0
});

=head1 NAME

MTComparEval::View::HTML - Catalyst TTSite View

=head1 SYNOPSIS

See L<MTComparEval>

=head1 DESCRIPTION

Catalyst TTSite View.

=head1 AUTHOR

A clever guy

=head1 LICENSE

This library is free software. You can redistribute it and/or modify
it under the same terms as Perl itself.

=cut

1;

