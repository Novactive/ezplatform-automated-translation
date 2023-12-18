# Usage

> All the configuration is SiteAccessAware then you can have different one depending on the SiteAccess

## Basic Configuration

```yaml
# app/config/config.yml

ez_platform_automated_translation:
    system:
        default:
            configurations:
                google:
                    apiKey: 'google-api-key'
                deepl:
                    authKey: 'deepl-pro-key'
                    baseUri: 'api-url' #NEEDED ONLY FOR FREE API#
                    nonSplittingTags: 'emphasis,subscript,superscript,strong'
                    supported_languages_mapping:
                        en_US: EN-US
                        en_GB: EN-GB
                        en_AU: EN
                        en_CA: EN
                        en_NZ: EN
                        es_ES: ES
                        bg_BG: BG
                        cs_CZ: CS
                        da_DK: DA
                        de_DE: DE
                        el_GR: EL
                        et_EE: ET
                        fi_FI: FI
                        fr_FR: FR
                        fr_BE: FR
                        fr_CA: FR
                        fr_CH: FR
                        hu_HU: HU
                        it_CH: IT
                        it_IT: IT
                        ja_JP: JP
                        ko_KR: KO
                        lt_LT: LT
                        lv_LV: LV
                        no_NO: NB
                        nl_BE: NL
                        nl_NL: NL
                        pl_PL: PL
                        pt_BR: PT-BR
                        pt_MZ: PT
                        pt_PT: PT-PT
                        ro_RO: RO
                        ru_RU: RU
                        sk_SK: SK
                        sl_SI: SL
                        sv_SE: SV
                        tr_TR: TR
                        uk_UA: UK
                        zh_CN: ZH
                        zh_HK: ZH
                        zh_TW: ZH
```

