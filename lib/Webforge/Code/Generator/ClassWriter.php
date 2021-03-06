<?php

namespace Webforge\Code\Generator;

use Webforge\Common\System\File;
use Webforge\Common\String as S;
use Webforge\Common\ArrayUtil as A;
use Webforge\Common\CodeWriter;
use RuntimeException;

/**
 * Writes a Class in Code (PHP)
 *
 * The ClassWriter writes the Stream in the gClass to a given File
 * 
 * This writer should change somehow, so that it does not use the GClass inner
 * Functions to generate the PHP from psc-cms and that its writing is own code
 */
class ClassWriter {
  
  const OVERWRITE = TRUE;
  
  /**
   * @var Webforge\Code\Generator\Imports
   */
  protected $imports;
  
  /**
   * @var Webforge\Common\CodeWriter
   */
  protected $codeWriter;
  
  /**
   * @var string
   */
  protected $namespaceContext;
  
  /**
   * @var Webforge\Code\Generator\Imports
   */
  protected $classImports;
  
  public function __construct() {
    $this->imports = new Imports();
  }
  
  public function write(GClass $gClass, File $file, $overwrite = FALSE) {
    if ($file->exists() && $overwrite !== self::OVERWRITE) {
      throw new ClassWritingException(
        sprintf('The file %s already exists. To overwrite set the overwrite parameter.', $file),
        ClassWritingException::OVERWRITE_NOT_SET
      );
    }
    
    $file->writeContents($this->writeGClassFile($gClass));
    return $this;
  }
  
  public function writeGClassFile(GClass $gClass, $eol = "\n") {
    $php = '<?php'.$eol;
    $php .= $eol;
    
    if (($namespace = $gClass->getNamespace()) != NULL) {
      $php .= 'namespace '.$namespace.';'.$eol;
      $php .= $eol;
    }
    
    $this->classImports = clone $this->imports;
    $this->classImports->mergeFromClass($gClass);
    
    if ($use = $this->classImports->php($namespace)) {
      $php .= $use;
      $php .= $eol;
    }
    
    $php .= $this->writeGClass($gClass, $namespace, $eol);
    $php .= $eol; // PSR-x

    return $php;
  }
  
  /**
   * returns the Class as PHP Code (without imports (use), without namespace decl)
   *
   * indentation is fixed: 2 whitespaces
   * @return string the code with docblock from class { to }
   */
  public function writeGClass(GClass $gClass, $namespace, $eol = "\n") {
    $that = $this;
    $this->namespaceContext = $namespace;
    
    $php = NULL;
    
    /* DocBlock */
    if ($gClass->hasDocBlock())
      $php .= $this->writeDocBlock($gClass->getDocBlock(), 0);
    
    /* Modifiers */
    $php .= $this->writeModifiers($gClass->getModifiers());
    
    /* Class */
    if ($gClass->isInterface()) {
      $php .= 'interface '.$gClass->getName().' ';
    } else {
      $php .= 'class '.$gClass->getName().' ';
    }
    
    /* Extends */
    if (($parent = $gClass->getParent()) != NULL) {
      // its important to use the contextNamespace here, because $namespace can be !== $gClass->getNamespace()
      if ($parent->getNamespace() === $namespace) {
        $php .= 'extends '.$parent->getName(); // don't prefix with namespace
      } else {
        // should it add to use, or use \FQN in extends?
        $php .= 'extends '.'\\'.$parent->getFQN();
      }
      $php .= ' ';
    }
    
    /* Interfaces */
    if (count($gClass->getInterfaces()) > 0) {
      $php .= 'implements ';
      $php .= A::implode($gClass->getInterfaces(), ', ', function (GClass $iClass) use ($namespace) {
        if ($iClass->getNamespace() === $namespace) {
          return $iClass->getName();
        } else {
          return '\\'.$iClass->getFQN();
        }
      });
      $php .= ' ';
    }
    
    $php .= '{'.$eol;
    
    /* those other methods make the margin with line breaks to top and to their left.*/
    
    /* Constants */
    $php .= A::joinc($gClass->getConstants(), '  '.$eol.'%s;'.$eol, function ($constant) use ($that, $eol) {
      return $that->writeConstant($constant, 2, $eol);
    });
    
    /* Properties */
    $php .= A::joinc($gClass->getProperties(), '  '.$eol.'%s;'.$eol, function ($property) use ($that, $eol) {
      return $that->writeProperty($property, 2, $eol);
    });

    /* Methods */
    $php .= A::joinc($gClass->getMethods(), '  '.$eol.'%s'.$eol, function ($method) use ($that, $eol) {
      return $that->writeMethod($method, 2, $eol); 
    });
    
    $php .= '}';
    
    return $php;
  }

  /**
   * returns the PHP Code for a GMethod
   *
   * after } is no LF
   * @return string
   */
  public function writeMethod(GMethod $method, $baseIndent = 0, $eol = "\n") {
    $php = NULL;
    
    if ($method->hasDocBlock()) {
      $php = $this->writeDocBlock($method->getDocBlock(), $baseIndent, $eol);
    }
    
    // vor die modifier muss das indent
    $php .= str_repeat(' ', $baseIndent);
    
    $php .= $this->writeModifiers($method->getModifiers());
    $php .= $this->writeGFunction($method, $baseIndent, $eol);
    
    return $php;
  }

  /**
   * returns PHPCode for a GFunction/GMethod
   *
   * nach der } ist kein LF
   */
  public function writeGFunction($function, $baseIndent = 0, $eol = "\n") {
    $php = NULL;
    
    $php .= $this->writeFunctionSignature($function, $baseIndent, $eol);
    
    if ($function->isAbstract() || $function->isInInterface()) {
      $php .= ';';
    } else {
      $php .= $this->writeFunctionBody($function->getBody(), $baseIndent, $eol);
    }
    
    return $php;
  }
  
  /**
   * Writes a function body
   *
   * the function body is from { to }
   * @return string
   */
  public function writeFunctionBody(GFunctionBody $body = NULL, $baseIndent = 0, $eol = "\n") {
    $php = NULL;
    
    $phpBody = $body ? $body->php($baseIndent+2, $eol).$eol : '';

    $php .= ' {';
    //if ($this->cbraceComment != NULL) { // inline comment wie dieser
      //$php .= ' '.$this->cbraceComment;
    //}
    $php .= $eol;
    $php .= $phpBody;
    $php .= S::indent('}', $baseIndent, $eol);
    
    return $php;
  }

  
  protected function writeFunctionSignature($function, $baseIndent = 0, $eol = "\n") {
    $php = 'function '.$function->getName().$this->writeParameters($function->getParameters(), $this->namespaceContext, $baseIndent, $eol);

    return $php;
  }
  
  public function writeParameters(Array $parameters, $namespace) {
    $that = $this;
    
    $php = '(';
    $php .= A::implode($parameters, ', ', function ($parameter) use ($that, $namespace) {
      return $that->writeParameter($parameter, $namespace);
    });
    $php .= ')';
    
    return $php;
  }
  
  public function writeParameter(GParameter $parameter, $namespace) {
    $php = '';
    
    $php .= $this->writeParameterTypeHint($parameter, $namespace);
    
    // name
    $php .= ($parameter->isReference() ? '&' : '').'$'.$parameter->getName();
    
    // optional (default)
    if ($parameter->hasDefault()) {
      $php .= ' = ';
      
      $default = $parameter->getDefault();
      if (is_array($this) && count($this) == 0) {
        $php .= 'array()';
      } else {
        $php .= $this->writeArgumentValue($default); // das sieht scheise aus
      }
    }
    
    return $php;
  }
  
  /**
   * @return string with whitespace at the end if hint is set
   */
  protected function writeParameterTypeHint(GParameter $parameter, $namespace) {
    if ($parameter->hasHint()) {
      
      if (($import = $parameter->getHintImport()) instanceof GClass) {
        
        if (isset($this->classImports) && $this->classImports->have($import)) {
          $useFQN = FALSE;
        } elseif ($this->imports->have($import)) {
          $useFQN = FALSE;
        } elseif ($import->getNamespace() === $namespace) {
          $useFQN = FALSE;
        } else {
          $useFQN = TRUE;
        }
        
        return $parameter->getHint($useFQN).' ';
      } else {
        return $parameter->getHint().' ';
      }
    }
    
    return '';
  }

  protected function writeArgumentValue($value) {
    if (is_array($value) && A::getType($value) === 'numeric') {
      return $this->getCodeWriter()->exportList($value);
    } elseif (is_array($value)) {
      return $this->getCodeWriter()->exportKeyList($value);
    } else {
      try {
        return $this->getCodeWriter()->exportBaseTypeValue($value);
      } catch (RuntimeException $e) {
        throw new \RuntimeException('In Argumenten oder Properties können nur Skalare DefaultValues stehen. Die value muss im Constructor stehen.', 0, $e);
      }
    }
  }

  /**
   * @return string
   */
  public function writeProperty(GProperty $property, $baseIndent, $eol = "\n") {
    $php = NULL;

    if ($property->hasDocBlock()) {
      $php = $this->writeDocBlock($property->getDocBlock(), $baseIndent, $eol);
    }

    $php .= str_repeat(' ', $baseIndent);
    
    $php .= $this->writeModifiers($property->getModifiers());

    $php .= '$'.$property->getName();
    
    if ($property->hasDefaultValue() && $property->getDefaultValue() !== NULL) {
      $php .= ' = '.$this->writePropertyValue($property->getDefaultValue());
    }

    return $php;
  }

  protected function writePropertyValue($value) {
    return $this->writeArgumentValue($value);
  }

  
  /**
   * @return string
   */
  public function writeDocBlock(DocBlock $docBlock, $baseIndent = 0, $eol = "\n") {
    return S::indent($docBlock->toString(), $baseIndent);
  }
  
  /**
   * @return string with whitespace after the last modifier
   */
  public function writeModifiers($bitmap) {
    $ms = array(GModifiersObject::MODIFIER_ABSTRACT => 'abstract',
                GModifiersObject::MODIFIER_PUBLIC => 'public',
                GModifiersObject::MODIFIER_PRIVATE => 'private',
                GModifiersObject::MODIFIER_PROTECTED => 'protected',
                GModifiersObject::MODIFIER_STATIC => 'static',
                GModifiersObject::MODIFIER_FINAL => 'final'
               );
    
    $php = NULL;
    foreach ($ms as $const => $modifier) {
      if (($const & $bitmap) == $const)
        $php .= $modifier.' ';
    }
    return $php;
  }
  
  /**
   * Adds an Import, that should be added to every written file
   * 
   */
  public function addImport(GClass $gClass, $alias = NULL) {
    $this->imports->add($gClass, $alias);
    return $this;
  }

  // @codeCoverageIgnoreStart
  /**
   * Removes an Import, that should be added to every written file
   *
   * @param string $alias case insensitive
   */
  public function removeImport($alias) {
    $this->imports->remove($alias);
    return $this;
  }
  
  /**
   * @param Webforge\Code\Generator\Imports $imports
   * @chainable
   */
  public function setImports(Imports $imports) {
    $this->imports = $imports;
    return $this;
  }
  // @codeCoverageIgnoreEnd  
  
  /**
   * @return Webforge\Code\Generator\Imports
   */
  public function getImports() {
    return $this->imports;
  }
  
  public function getCodeWriter() {
    if (!isset($this->codeWriter)) {
      $this->codeWriter = new CodeWriter;
    }
    
    return $this->codeWriter;
  }
}
