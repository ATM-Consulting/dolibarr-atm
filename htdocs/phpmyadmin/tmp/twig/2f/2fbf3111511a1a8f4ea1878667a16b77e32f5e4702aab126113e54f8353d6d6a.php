<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* table/find_replace/replace_preview.twig */
class __TwigTemplate_2a29c0ffcf05468f4f188a4eb8abbd74a24ca1bca664d5e67412236d24cc906d extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<form method=\"post\" action=\"";
        echo PhpMyAdmin\Url::getFromRoute("/table/find-replace");
        echo "\">
  ";
        // line 2
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
  <input type=\"hidden\" name=\"replace\" value=\"true\">
  <input type=\"hidden\" name=\"columnIndex\" value=\"";
        // line 4
        echo twig_escape_filter($this->env, ($context["column_index"] ?? null), "html", null, true);
        echo "\">
  <input type=\"hidden\" name=\"findString\" value=\"";
        // line 5
        echo twig_escape_filter($this->env, ($context["find"] ?? null), "html", null, true);
        echo "\">
  <input type=\"hidden\" name=\"replaceWith\" value=\"";
        // line 6
        echo twig_escape_filter($this->env, ($context["replace_with"] ?? null), "html", null, true);
        echo "\">
  <input type=\"hidden\" name=\"useRegex\" value=\"";
        // line 7
        echo twig_escape_filter($this->env, ($context["use_regex"] ?? null), "html", null, true);
        echo "\">

  <div class=\"card\">
    <div class=\"card-header\">";
echo _gettext("Find and replace - preview");
        // line 10
        echo "</div>

    <div class=\"card-body\">
      <table class=\"table table-striped w-auto\">
        <thead>
          <tr>
            <th>";
echo _gettext("Count");
        // line 16
        echo "</th>
            <th>";
echo _gettext("Original string");
        // line 17
        echo "</th>
            <th>";
echo _gettext("Replaced string");
        // line 18
        echo "</th>
          </tr>
        </thead>
        <tbody>
          ";
        // line 22
        if (twig_test_iterable(($context["result"] ?? null))) {
            // line 23
            echo "            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["result"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
                // line 24
                echo "              <tr>
                <td class=\"text-end\">";
                // line 25
                echo twig_escape_filter($this->env, (($__internal_compile_0 = $context["row"]) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[2] ?? null) : null), "html", null, true);
                echo "</td>
                <td>";
                // line 26
                echo twig_escape_filter($this->env, (($__internal_compile_1 = $context["row"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1[0] ?? null) : null), "html", null, true);
                echo "</td>
                <td>";
                // line 27
                echo twig_escape_filter($this->env, (($__internal_compile_2 = $context["row"]) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2[1] ?? null) : null), "html", null, true);
                echo "</td>
              </tr>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 30
            echo "          ";
        }
        // line 31
        echo "        </tbody>
      </table>
    </div>

    <div class=\"card-footer\">
      <input class=\"btn btn-secondary\" type=\"submit\" name=\"replace\" value=\"";
echo _gettext("Replace");
        // line 36
        echo "\">
    </div>
  </div>
</form>
";
    }

    public function getTemplateName()
    {
        return "table/find_replace/replace_preview.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  127 => 36,  119 => 31,  116 => 30,  107 => 27,  103 => 26,  99 => 25,  96 => 24,  91 => 23,  89 => 22,  83 => 18,  79 => 17,  75 => 16,  66 => 10,  59 => 7,  55 => 6,  51 => 5,  47 => 4,  42 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/find_replace/replace_preview.twig", "/home/groupe-cara/domains/mycara.groupe-cara.fr/public_html/htdocs/phpmyadmin/templates/table/find_replace/replace_preview.twig");
    }
}
