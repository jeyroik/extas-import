![tests](https://github.com/jeyroik/extas-installer-import/workflows/PHP%20Composer/badge.svg?branch=master&event=push)
![codecov.io](https://codecov.io/gh/jeyroik/extas-installer-import/coverage.svg?branch=master)

[![Latest Stable Version](https://poser.pugx.org/jeyroik/extas-installer-import/v)](//packagist.org/packages/jeyroik/extas-jsonrpc)
[![Total Downloads](https://poser.pugx.org/jeyroik/extas-installer-import/downloads)](//packagist.org/packages/jeyroik/extas-jsonrpc)
[![Dependents](https://poser.pugx.org/jeyroik/extas-installer-import/dependents)](//packagist.org/packages/jeyroik/extas-jsonrpc)


# Описание

Пакет позволяет импортировать только нужные сущности из extas-совместимых пакетов.

Примечание: донорский пакет должен поддерживать экспорт (подробности см. ниже).

# Настройка экспорта

Если пакет желает организовать установку каких-либо сущностей по требованию, то ему необходимо описать секцию экспорта. 

`extas.json`
```json
{
  "name": "my/package",
  "export": {
    
  }
}
```

Содержание экспорта идентично содержанию `extas.json`, т.е. например, если в пакете подразумеваются плагины, которые необходимо установить в любом случае и плагины, которые можно устанавливать по желанию, то описание будет выглядеть примерно так:

`extas.json`
```json
{
  "name": "my/package",
  "plugins": [
    {
      "class": "plugin/First",
      "stage": "some.stage"
    }
  ],
  "export": {
    "plugins": [
      {
        "class": "plugin/Second",
        "stage": "any.stage",
        "install_on": "install"
      }
    ]
  }
}
```

`Внимание:` для плагинов и расширений необходимо учитывать тот факт, что механизм импорта подключается на этапе установки (т.е. после инициализации). В связи с этим, для плагинов и расширений на экспорт, необходимо явно указывать стадию установки (`install_on: install`).

# Настройка импорта

`extas.json`
```json
{
  "name": "my/package2",
  "import": {
    "from": {
      "my/package": {
        "plugins": "plugin/Second"
      }
    },
    "parameters": {
      "on_miss_package": {
        "name": "on_miss_package",
        "value": "continue"
      },
      "on_miss_section": {
        "name": "on_miss_package",
        "value": "throw"
      }
    }
  }
}
```

- `from` в этом разделе указываются пакеты, из которых требуется произвести импорт.
- `parameters` параметры импорта, определяют поведение в случаях, когда не найден пакет для импорта или запрашиваемая секция внутри него. Возможные значения: `continue` - просто перейти к следующему пакету/следующей секции, `throw` - выбросить ошибку, весь импорт прерывается.

Пакет предоставляет две стадии (они запускаются именно в следующем порядке):
- `extas.package.export.build.<section.name>` - например, `extas.package.export.build.plugins` для плагинов.
- `extas.package.export.build`

Интерфейс стадий идентичный, его можно найти в `src/interfaces/stages/IStagePackageExportBuild`.

Данные стадии предназначены для интерпретации значения секций в разделе `from`. Таким образом, имеется возможность организовать свой формат.

## Формат описания импорта из коробки

Из коробки пакет предоставляет механизм поиска сущностей по полю - `extas\components\plugins\export\PluginExportByField`.

Данный механизм позволяет в параметрах плагина указать поле и по нему фильтровать сущности:

```json
{
  "name": "my/package2",
  "import": {
    "from": {
      "my/package": {
        "plugins": "plugin/Second"
      }
    }
  }
}
```

Из коробки плагин обрабатывает секции `plugins` и `extensions`. По вышеприведённому примеру, из пакета `my/package` будет импортирован только плагин с классом `plugin/Second`.

Также имеется возможность указать несколько сущностей:

```json
{
  "name": "my/package2",
  "import": {
    "from": {
      "my/package": {
        "plugins": ["plugin/Second", "plugin/Third"]
      }
    }
  }
}
```

Пример описания плагина с указанием поля см. в `extas.json` данного пакета.