import React from 'react';
import PropTypes from 'prop-types';
import Icon from '@ibexa-admin-ui/src/bundle/ui-dev/src/modules/common/icon/icon';

const SelectedLocationComponent = (props) => {
    const { location, onDelete } = props;
    const handleClick = function (event) {
        onDelete(event.currentTarget.dataset.locationId);
    };
    return (
        <li className="path-location" >
            <div className="pull-left">
                <span className="c-ct-list-item__icon">
                    <Icon name={location.ContentInfo.Content.ContentTypeInfo.identifier} extraClasses="ibexa-icon ibexa-icon--small ibexa-icon--dark" />
                </span>
                {location.ContentInfo.Content.Name}
                <a data-location-id={location.id} onClick={handleClick}  className="btn ibexa-btn ibexa-btn--ghost ibexa-btn--no-text delete-rss-items pull-right">
                    <Icon name="trash" extraClasses="ibexa-icon ibexa-icon--small ibexa-icon--dark" />
                </a>
            </div>
        </li>
    );
};

SelectedLocationComponent.propTypes = {
    location: PropTypes.object,
    onDelete: PropTypes.func,
};

SelectedLocationComponent.defaultProps = {
    location: null
};

export default SelectedLocationComponent;
