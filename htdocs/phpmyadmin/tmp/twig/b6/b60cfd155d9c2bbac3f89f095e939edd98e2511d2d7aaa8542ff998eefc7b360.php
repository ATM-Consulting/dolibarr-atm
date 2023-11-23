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

/* table/find_replace/index.twig */
class __TwigTemplate_fa73c5a026f3a0e1b6d501e256a674283cc7f87c5164beabd7bbb22426d68b13 extends Template
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
        echo "<ul class=\"nav nav-pills m-2\">
  <li class=\"nav-item\">
    <a class=\"nav-link\" href=\"";
        // line 3
        echo PhpMyAdmin\Url::getFromRoute("/table/search", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "pos" => 0]);
        echo "\">
      ";
        // line 4
        echo PhpMyAdmin\Html\Generator::getIcon("b_search", _gettext("Table search"), false, false, "TabsMode");
        echo "
    </a>
  </li>

  <li class=\"nav-item\">
    <a class=\"nav-link\" href=\"";
        // line 9
        echo PhpMyAdmin\Url::getFromRoute("/table/zoom-search", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
        echo "\">
      ";
        // line 10
        echo PhpMyAdmin\Html\Generator::getIcon("b_select", _gettext("Zoom search"), false, false, "TabsMode");
        echo "
    </a>
  </li>

  <li class=\"nav-item\">
    <a class=\"nav-link active\" href=\"";
        // line 15
        echo PhpMyAdmin\Url::getFromRoute("/table/find-replace", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
        echo "\">
      ";
        // line 16
        echo PhpMyAdmin\Html\Generator::getIcon("b_find_replace", _gettext("Find and replace"), false, false, "TabsMode");
        echo "
    </a>
  </li>
</ul>

<form method=\"post\" action=\"";
        // line 21
        echo PhpMyAdmin\Url::getFromRoute("/table/find-replace");
        echo "\" name=\"insertForm\" id=\"find_replace_form\" class=\"ajax lock-page\">
  ";
        // line 22
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
  <input type=\"hidden\" name=\"goto\" value=\"";
        // line 23
        echo twig_escape_filter($this->env, ($context["goto"] ?? null), "html", null, true);
        echo "\">
  <input type=\"hidden\" name=\"back\" value=\"";
        // line 24
        echo PhpMyAdmin\Url::getFromRoute("/table/find-replace");
        echo "\">

  <div class=\"card\">
    <div class=\"card-header\">";
echo _gettext("Find and replace");
        // line 27
        echo "</div>

    <div class=\"card-body\">
      <div class=\"mb-3\">
        <label class=\"form-label\" for=\"findInput\">";
echo _gettext("Find:");
        // line 31
        echo "</label>
        <input class=\"form-control\" type=\"text\" value=\"\" name=\"find\" id=\"findInput\" required>
      </div>

      <div class=\"mb-3\">
        <label class=\"form-label\" for=\"replaceWithInput\">";
echo _gettext("Replace with:");
        // line 36
        echo "</label>
        <input class=\"form-control\" type=\"text\" value=\"\" name=\"replaceWith\" id=\"replaceWithInput\">
      </div>

      <div class=\"mb-3\">
        <label class=\"form-label\" for=\"columnIndexSelect\">";
echo _gettext("Column:");
        // line 41
        echo "</label>
        <select class=\"form-select\" name=\"columnIndex\" id=\"columnIndexSelect\">
          ";
        // line 43
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(range(0, (twig_length_filter($this->env, ($context["column_names"] ?? null)) - 1)));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            // line 44
            echo "            ";
            $context["type"] = (($__internal_compile_0 = ($context["types"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[(($__internal_compile_1 = ($context["column_names"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1[$context["i"]] ?? null) : null)] ?? null) : null);
            // line 45
            echo "
            ";
            // line 46
            if ((0 === twig_compare(twig_get_attribute($this->env, $this->source, ($context["sql_types"] ?? null), "getTypeClass", [0 => ($context["type"] ?? null)], "method", false, false, false, 46), "CHAR"))) {
                // line 47
                echo "              <option value=\"";
                echo twig_escape_filter($this->env, $context["i"], "html", null, true);
                echo "\">";
                // line 48
                echo twig_escape_filter($this->env, (($__internal_compile_2 = ($context["column_names"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2[$context["i"]] ?? null) : null), "html", null, true);
                // line 49
                echo "</option>
            ";
            }
            // line 51
            echo "          ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 52
        echo "        </select>
      </div>

      <div class=\"form-check\">
        <input class=\"form-check-input\" type=\"checkbox\" name=\"useRegex\" id=\"useRegex\">
        <label class=\"form-check-label\" for=\"useRegex\">";
echo _gettext("Use regular expression");
        // line 57
        echo "</label>
      </div>
    </div>

    <div class=\"card-footer\">
      <input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\"";
echo _gettext("Go");
        // line 62
        echo "\">
    </div>
  </div>
</form>
<div id=\"sqlqueryresultsouter\"></div>
";
    }

    public function getTemplateName()
    {
        return "table/find_replace/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  167 => 62,  159 => 57,  151 => 52,  145 => 51,  141 => 49,  139 => 48,  135 => 47,  133 => 46,  130 => 45,  127 => 44,  123 => 43,  119 => 41,  111 => 36,  103 => 31,  96 => 27,  89 => 24,  85 => 23,  81 => 22,  77 => 21,  69 => 16,  65 => 15,  57 => 10,  53 => 9,  45 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/find_replace/index.twig", "/home/groupe-cara/domains/mycara.groupe-cara.fr/public_html/htdocs/phpmyadmin/templates/table/find_replace/index.twig");
    }
}
